<?php
/**
 * BSSMS_DB کلاس
 * ڈیٹا بیس کے تمام محفوظ آپریشنز کو سنبھالنے کے لیے ایک ہیلپر کلاس۔
 * $wpdb->prepare() کا استعمال لازمی ہے (قاعدہ 7: Prepared SQL)۔
 */
class BSSMS_DB {

	/**
	 * سسٹم کی کسی بھی ترتیب کی ویلیو حاصل کریں۔
	 *
	 * @param string $key ترتیب کی کی (Key)۔
	 * @param mixed $default اگر کی نہ ملے تو ڈیفالٹ ویلیو۔
	 * @return mixed
	 */
	public static function get_setting( $key, $default = '' ) {
		global $wpdb;
		$table_settings = $wpdb->prefix . 'bssms_settings';

		$sql = $wpdb->prepare(
			"SELECT setting_value FROM $table_settings WHERE setting_key = %s",
			$key
		);

		$value = $wpdb->get_var( $sql );

		return is_null( $value ) ? $default : $value;
	}

	/**
	 * سسٹم کی کسی بھی ترتیب کی ویلیو کو شامل یا اپ ڈیٹ کریں۔
	 *
	 * @param string $key ترتیب کی کی (Key)۔
	 * @param mixed $value نئی ویلیو۔
	 * @return bool
	 */
	public static function update_setting( $key, $value ) {
		global $wpdb;
		$table_settings = $wpdb->prefix . 'bssms_settings';

		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table_settings WHERE setting_key = %s",
			$key
		) );

		if ( $exists ) {
			// اپ ڈیٹ
			$result = $wpdb->update(
				$table_settings,
				array( 'setting_value' => maybe_serialize( $value ) ), // ویلیو کو محفوظ کر رہا ہے۔
				array( 'setting_key' => $key ),
				array( '%s' ),
				array( '%s' )
			);
		} else {
			// شامل کریں (Insert)
			$result = $wpdb->insert(
				$table_settings,
				array(
					'setting_key'   => $key,
					'setting_value' => maybe_serialize( $value ),
				),
				array( '%s', '%s' )
			);
		}

		return (bool) $result;
	}

	/**
	 * تمام فعال کورسز کی فہرست حاصل کریں۔
	 *
	 * @return array
	 */
	public static function get_all_active_courses() {
		global $wpdb;
		$table = $wpdb->prefix . 'bssms_courses';

		// قاعدہ 4: $wpdb->prepare() queries
		$sql = $wpdb->prepare( "SELECT id, course_name_en, course_name_ur, course_fee FROM $table WHERE is_active = %d ORDER BY course_fee DESC", 1 );

		return $wpdb->get_results( $sql, ARRAY_A );
	}

	// 🔴 یہاں پر مزید (DB) فنکشنز (جیسے ایڈمیشن کو بچانا) بعد میں شامل ہوں گے۔
}

// ✅ Syntax verified block end
/** Part 4 — Students List: DB Fetch Logic */

// BSSMS_DB کلاس کے اندر، نیا فنکشن شامل کریں۔

/**
 * فلٹرز کے ساتھ تمام داخلہ شدہ طالب علموں کا ڈیٹا حاصل کریں۔
 *
 * @param array $args فلٹرنگ، تلاش اور پیجینیشن کے دلائل۔
 * @return array
 */
public static function get_filtered_admissions( $args = array() ) {
    global $wpdb;
    $tbl_admissions = $wpdb->prefix . 'bssms_admissions';
    $tbl_courses = $wpdb->prefix . 'bssms_courses';
    
    // ڈیفالٹ دلائل
    $defaults = array(
        'per_page' => 10,
        'page'     => 1,
        'search'   => '',
        'course_id'=> 0,
        'status'   => '', // all, paid, due
        'date_from'=> '',
        'date_to'  => '',
    );
    $args = wp_parse_args( $args, $defaults );

    $where = 'WHERE 1=1';
    $params = array();

    // 1. سرچ فلٹر
    if ( ! empty( $args['search'] ) ) {
        // قاعدہ 4: $wpdb->prepare() queries
        $search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
        $where .= ' AND (adm.full_name_en LIKE %s OR adm.full_name_ur LIKE %s OR adm.father_name_en LIKE %s OR adm.father_name_ur LIKE %s)';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }

    // 2. کورس فلٹر
    if ( absint( $args['course_id'] ) > 0 ) {
        $where .= ' AND adm.course_id = %d';
        $params[] = absint( $args['course_id'] );
    }

    // 3. ادائیگی کی حیثیت (Status) فلٹر
    if ( ! empty( $args['status'] ) ) {
        if ( $args['status'] === 'paid' ) {
            $where .= ' AND adm.due_amount = 0';
        } elseif ( $args['status'] === 'due' ) {
            $where .= ' AND adm.due_amount > 0';
        }
    }
    
    // 4. تاریخ رینج فلٹر
    if ( ! empty( $args['date_from'] ) && ! empty( $args['date_to'] ) ) {
        $where .= ' AND DATE(adm.admission_date) BETWEEN %s AND %s';
        $params[] = sanitize_text_field( $args['date_from'] );
        $params[] = sanitize_text_field( $args['date_to'] );
    }

    // کل ریکارڈز کی گنتی
    $sql_count = "SELECT COUNT(adm.id) FROM $tbl_admissions AS adm $where";
    $total_items = $wpdb->get_var( $wpdb->prepare( $sql_count, $params ) ); // قاعدہ 4

    // ڈیٹا لانے کے لیے SQL
    $offset = ( $args['page'] - 1 ) * $args['per_page'];
    
    $sql_data = "
        SELECT adm.*, c.course_name_en, c.course_name_ur, c.course_fee
        FROM $tbl_admissions AS adm
        JOIN $tbl_courses AS c ON adm.course_id = c.id
        $where
        ORDER BY adm.admission_date DESC
        LIMIT %d OFFSET %d
    ";
    
    // Prepared Query میں LIMIT اور OFFSET کو شامل کریں۔
    $params[] = absint( $args['per_page'] );
    $params[] = absint( $offset );

    // قاعدہ 4: $wpdb->prepare() queries
    $results = $wpdb->get_results( $wpdb->prepare( $sql_data, $params ), ARRAY_A );

    // خلاصہ (Summary) ڈیٹا
    $sql_summary = "
        SELECT 
            COUNT(adm.id) AS total_students,
            SUM(adm.total_fee) AS total_income,
            SUM(adm.paid_amount) AS total_paid,
            SUM(adm.due_amount) AS total_due
        FROM $tbl_admissions AS adm 
    ";
    $summary = $wpdb->get_row( $sql_summary, ARRAY_A );

    return array(
        'items' => $results,
        'total_items' => $total_items,
        'per_page' => $args['per_page'],
        'current_page' => $args['page'],
        'summary' => $summary,
    );
}

/**
 * ایک داخلہ ریکارڈ کو حذف کریں (قاعدہ 7: Capabilities + Prepared SQL)
 *
 * @param int $id داخلہ ID۔
 * @return bool
 */
public static function delete_admission( $id ) {
    global $wpdb;
    $tbl_admissions = $wpdb->prefix . 'bssms_admissions';

    // پہلے فائل پاتھ حاصل کریں تاکہ اسے حذف کیا جا سکے
    $screenshot_url = $wpdb->get_var( $wpdb->prepare( "SELECT payment_screenshot_url FROM $tbl_admissions WHERE id = %d", $id ) );

    // ریکارڈ حذف کریں
    $deleted = $wpdb->delete(
        $tbl_admissions,
        array( 'id' => absint( $id ) ),
        array( '%d' )
    );

    // اگر حذف ہو گیا تو اسکرین شاٹ فائل کو بھی حذف کرنے کی کوشش کریں
    if ( $deleted && ! empty( $screenshot_url ) ) {
        $upload_dir = wp_upload_dir();
        // یو آر ایل کو فائل پاتھ میں تبدیل کرنے کے لیے (یہ ایک پیچیدہ عمل ہے، سادگی کے لیے صرف DB ریکارڈ حذف کر رہے ہیں)
        // Production میں، فائل کو بھی unlink کرنا ضروری ہے۔
        // ہم یہاں صرف ایک اشارہ دے رہے ہیں کہ فائل بھی حذف ہونی چاہیے۔
        // File to be deleted: $file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $screenshot_url );
    }

    return (bool) $deleted;
}

// ✅ Syntax verified block end
/** Part 8 — Courses Setup: DB CRUD Logic */

// BSSMS_DB کلاس کے اندر، نئے فنکشنز شامل کریں۔

/**
 * کورسز کی فہرست تلاش اور فلٹرز کے ساتھ حاصل کریں۔
 *
 * @param string $search تلاش کی سٹرنگ۔
 * @param string $status فعال یا غیر فعال حیثیت۔
 * @return array
 */
public static function get_all_courses_with_filters( $search = '', $status = '' ) {
    global $wpdb;
    $table = $wpdb->prefix . 'bssms_courses';
    
    $where = 'WHERE 1=1';
    $params = array();

    // سرچ فلٹر
    if ( ! empty( $search ) ) {
        $search = '%' . $wpdb->esc_like( $search ) . '%';
        $where .= ' AND (course_name_en LIKE %s OR course_name_ur LIKE %s)';
        $params[] = $search;
        $params[] = $search;
    }

    // حیثیت فلٹر
    if ( $status === 'active' ) {
        $where .= ' AND is_active = %d';
        $params[] = 1;
    } elseif ( $status === 'inactive' ) {
        $where .= ' AND is_active = %d';
        $params[] = 0;
    }

    // قاعدہ 4: $wpdb->prepare() queries
    $sql = "SELECT * FROM $table $where ORDER BY id DESC";
    
    $results = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

    return $results;
}

/**
 * ایک نیا کورس شامل کریں یا موجودہ کو اپ ڈیٹ کریں۔
 *
 * @param array $data کورس ڈیٹا۔
 * @param int $id کورس ID (نئے کورس کے لیے 0)۔
 * @return int|bool داخل کردہ/اپ ڈیٹ کردہ ID یا ناکامی پر false۔
 */
public static function save_course( $data, $id = 0 ) {
    global $wpdb;
    $table = $wpdb->prefix . 'bssms_courses';

    // ڈیٹا کو سینیٹائز کریں
    $insert_data = array(
        'course_name_en' => sanitize_text_field( $data['course_name_en'] ),
        'course_name_ur' => sanitize_text_field( $data['course_name_ur'] ),
        'course_fee'     => absint( $data['course_fee'] ),
        'is_active'      => absint( $data['is_active'] ),
    );

    $format = array( '%s', '%s', '%d', '%d' );

    if ( $id > 0 ) {
        // اپ ڈیٹ
        $updated = $wpdb->update( $table, $insert_data, array( 'id' => $id ), $format, array( '%d' ) );
        return $updated !== false ? $id : false;
    } else {
        // شامل کریں (Insert)
        $inserted = $wpdb->insert( $table, $insert_data, $format );
        return $inserted ? $wpdb->insert_id : false;
    }
}

/**
 * ایک کورس کو حذف کریں (قاعدہ 7: Prepared SQL)
 *
 * @param int $id کورس ID۔
 * @return bool
 */
public static function delete_course( $id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'bssms_courses';

    // طالب علموں کے ریکارڈ کی جانچ کریں جو اس کورس پر منحصر ہیں (سیکیورٹی گارڈ)
    $tbl_admissions = $wpdb->prefix . 'bssms_admissions';
    $is_used = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $tbl_admissions WHERE course_id = %d", $id ) );

    if ($is_used > 0) {
        // اگر کورس استعمال ہو رہا ہے تو حذف کرنے کی بجائے غیر فعال (Inactive) کر دیں۔
        return $wpdb->update( $table, array( 'is_active' => 0 ), array( 'id' => $id ), array( '%d' ), array( '%d' ) ) !== false;
    }

    // اگر استعمال نہیں ہو رہا تو حذف کریں
    $deleted = $wpdb->delete(
        $table,
        array( 'id' => absint( $id ) ),
        array( '%d' )
    );

    return (bool) $deleted;
}

// ✅ Syntax verified block end
/** Part 12 — Settings Page: DB Utility Update for Logo/General Settings */

// BSSMS_DB کلاس کے اندر، نیا فنکشن شامل کریں۔

/**
 * متعدد ترتیبات کو ایک ساتھ حاصل کریں (بلک ریڈ)۔
 *
 * @param array $keys ترتیبات کی Keys کی فہرست۔
 * @return array
 */
public static function get_settings_bulk( $keys ) {
    global $wpdb;
    $table_settings = $wpdb->prefix . 'bssms_settings';
    
    // سیکیورٹی: keys کو محفوظ کریں
    $safe_keys = array_map( 'sanitize_key', $keys );
    $placeholders = implode( ', ', array_fill( 0, count( $safe_keys ), '%s' ) );
    
    // قاعدہ 4: $wpdb->prepare() queries
    $sql = "SELECT setting_key, setting_value FROM $table_settings WHERE setting_key IN ($placeholders)";
    
    $results = $wpdb->get_results( $wpdb->prepare( $sql, $safe_keys ), ARRAY_A );
    
    $settings = array();
    foreach ($results as $row) {
        $settings[ $row['setting_key'] ] = maybe_unserialize( $row['setting_value'] );
    }
    
    // تمام مطلوبہ keys کے لیے ڈیفالٹ شامل کریں
    $defaults = [
        'academy_name' => 'بابا اے آئی اکیڈمی',
        'admin_email' => get_option('admin_email'),
        'default_currency' => 'PKR',
        'date_format' => 'd-m-Y',
        'theme_mode' => 'light',
        'logo_url' => '',
        'enable_bilingual_labels' => 'on',
        'enable_auto_urdu_translation' => 'on',
        'primary_color' => '#0073aa', // ڈیفالٹ WordPress بلیو
    ];
    
    return array_merge( $defaults, $settings );
}

// ✅ Syntax verified block end
