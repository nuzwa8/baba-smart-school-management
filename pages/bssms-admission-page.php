<?php
/**
 * BSSMS_Admission_Page کلاس
 * داخلہ فارم (Admission Form) کے صفحہ کی (PHP) لاجک اور ٹیمپلیٹ کو سنبھالتی ہے۔
 * قاعدہ 30 کے تحت یہ ایک سرشار (Dedicated) فائل ہے۔
 */
class BSSMS_Admission_Page {

	/**
	 * داخلہ فارم کے صفحہ کو رینڈر کریں۔
	 */
	public static function render_page() {
		// قاعدہ 4: ہر پیج Root : <div id="bssms-*-root">
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'نیا داخلہ فارم', 'bssms' ); ?> <span style="font-size:14px; color:#999; margin-left:10px;">(Student Enrolment + Fee Entry)</span></h2>
			<div class="bssms-message-container"></div>
			<div id="bssms-admission-root">
				<?php 
				self::render_admission_template();
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * داخلہ فارم کے لیے (PHP) ٹیمپلیٹ بلاک کو رینڈر کریں۔
	 * قاعدہ 4: مکمل <template> blocks
	 */
	private static function render_admission_template() {
		// ہم فارم کو ماؤنٹ کرنے کے لیے صرف ایک خالی <template> استعمال کر رہے ہیں۔
		// مکمل فارم کا HTML (JavaScript) فیز میں بنایا جائے گا۔
		?>
		<template id="bssms-admission-form-template">
			<div class="bssms-form-wrapper">
				<form id="bssms-admission-form" class="bssms-card bssms-form-grid" enctype="multipart/form-data">
					
					<div class="bssms-card-section" data-label="Personal Information">
						<h4 class="section-title">👤 ذاتی معلومات (Personal Information)</h4>
						
						<div class="bssms-form-group">
							<label for="full_name_en" class="bssms-label">Full Name (English) <span class="required">*</span></label>
							<input type="text" id="full_name_en" name="full_name_en" class="bssms-input bssms-input-en" required placeholder="مثلاً: Ali Ahmed">
							<small class="bssms-hint">نام (اردو میں خودکار)</small>
							<input type="text" id="full_name_ur" name="full_name_ur" class="bssms-input bssms-input-ur" readonly placeholder="علی احمد">
						</div>

						<div class="bssms-form-group">
							<label for="father_name_en" class="bssms-label">Father Name (English) <span class="required">*</span></label>
							<input type="text" id="father_name_en" name="father_name_en" class="bssms-input bssms-input-en" required placeholder="مثلاً: Muhammad Akram">
							<small class="bssms-hint">والد کا نام (اردو میں خودکار)</small>
							<input type="text" id="father_name_ur" name="father_name_ur" class="bssms-input bssms-input-ur" readonly placeholder="محمد اکرم">
						</div>

						<div class="bssms-form-group">
							<label class="bssms-label">Gender (جنس) <span class="required">*</span></label>
							<div class="bssms-radio-group">
								<input type="radio" id="gender_male" name="gender" value="Male" required>
								<label for="gender_male">Male (مرد)</label>
								
								<input type="radio" id="gender_female" name="gender" value="Female">
								<label for="gender_female">Female (عورت)</label>
								
								<input type="radio" id="gender_other" name="gender" value="Other">
								<label for="gender_other">Other (دیگر)</label>
							</div>
						</div>
						
					</div>
					
					<div class="bssms-card-section" data-label="Course and Fee">
						<h4 class="section-title">🎓 کورس اور فیس کی تفصیلات (Course Details)</h4>
						
						<div class="bssms-form-group">
							<label for="dob" class="bssms-label">Date of Birth (تاریخ پیدائش) <span class="required">*</span></label>
							<input type="date" id="dob" name="dob" class="bssms-input" required>
						</div>
						
						<div class="bssms-form-group">
							<label for="course_select" class="bssms-label">Select Course (کورس منتخب کریں) <span class="required">*</span></label>
							<select id="course_select" name="course_id" class="bssms-select" required>
								<option value="">--- کورس منتخب کریں ---</option>
							</select>
						</div>

						<div class="bssms-form-group">
							<label for="total_fee" class="bssms-label">Total Fee (کل فیس)</label>
							<input type="number" id="total_fee" name="total_fee" class="bssms-input bssms-fee-display" readonly value="0">
						</div>
						
						<div class="bssms-form-group">
							<label for="paid_amount" class="bssms-label">Paid Amount (ادا شدہ رقم) <span class="required">*</span></label>
							<input type="number" id="paid_amount" name="paid_amount" class="bssms-input bssms-input-fee" required min="0" placeholder="مثلاً: 10000">
							<p class="bssms-fee-words" id="paid_amount_words">صفر روپے</p> </div>
						
						<div class="bssms-form-group">
							<label for="due_amount" class="bssms-label">Due Amount (بقایا رقم)</label>
							<input type="number" id="due_amount" name="due_amount" class="bssms-input bssms-fee-display" readonly value="0">
							<p class="bssms-fee-words bssms-due-amount" id="due_amount_words">صفر روپے بقایا</p>
						</div>
						
					</div>
					
					<div class="bssms-card-section bssms-col-span-full" data-label="Smart and Payment Features">
						<div class="bssms-row-flex">
							<div class="bssms-feature-card">
								<h4 class="section-title">✨ اسمارٹ فیچرز (Smart Features)</h4>
								<div class="bssms-form-group">
									<label for="payment_screenshot" class="bssms-label">Upload Screenshot (ادائیگی کا اسکرین شاٹ) <span class="required">*</span></label>
									<input type="file" id="payment_screenshot" name="payment_screenshot" class="bssms-input-file" accept="image/*" required>
									<p class="bssms-file-preview" id="file_preview_name">فائل منتخب نہیں کی گئی</p>
								</div>
								<button type="button" class="bssms-btn bssms-btn-secondary" id="translate_urdu_btn">🇵🇰 Translate Name Fields to Urdu (خودکار ترجمہ)</button>
							</div>

							<div class="bssms-feature-card">
								<h4 class="section-title">💳 ادائیگی کا طریقہ (Payment Method)</h4>
								<div class="bssms-form-group">
									<label for="payment_method" class="bssms-label">Method <span class="required">*</span></label>
									<select id="payment_method" name="payment_method" class="bssms-select" required>
										<option value="Cash">Cash (نقدی)</option>
										<option value="Bank Transfer">Bank Transfer (بینک ٹرانسفر)</option>
										<option value="Easypaisa/JazzCash">Easypaisa/JazzCash</option>
									</select>
								</div>
								<button type="button" class="bssms-btn bssms-btn-secondary" id="convert_to_words_btn">Convert Numbers to Words (الفاظ میں تبدیلی)</button>
							</div>
						</div>
					</div>
					
					<div class="bssms-form-actions bssms-col-span-full">
						<button type="submit" class="bssms-btn bssms-btn-primary" id="save_admission_btn">💾 Save (محفوظ کریں)</button>
						<button type="reset" class="bssms-btn bssms-btn-secondary">Reset (دوبارہ سیٹ)</button>
						<button type="button" class="bssms-btn bssms-btn-info" id="print_btn">🖨️ Print (پرنٹ)</button>
						<button type="button" class="bssms-btn bssms-btn-info" id="export_excel_btn">📊 Export (ایکسل)</button>
					</div>
					
				</form>
			</div>
			
			<div id="bssms-admission-success-card" class="bssms-success-card" style="display: none;">
				</div>
			
		</template>
		<?php
	}
}

// ✅ Syntax verified block end
