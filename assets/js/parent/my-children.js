/**
 * BSSMS Parent 'My Children'
 * * سخت پابندی: یہ فائل صرف UI کو ماؤنٹ کرتی ہے اور AJAX پلیس ہولڈرز پر مشتمل ہے۔
 */

// 🟢 یہاں سے [Parent My Children JS] شروع ہو رہا ہے
(function () {
	'use strict';

	// ضروری یوٹیلیٹیز (Utilities) کے لیے پلیس ہولڈرز
	const BSSMS_Utils = window.BSSMS_Utils || {
		mountTemplate: (rootId, templateId) => {
			console.log(`Mounting ${templateId} into ${rootId}`);
			const root = document.getElementById(rootId);
			const template = document.getElementById(templateId);
			if (root && template) {
				root.innerHTML = template.innerHTML;
			} else {
				console.error(`Root (${rootId}) or Template (${templateId}) not found.`);
			}
		},
		wpAjax: (options) => {
			console.log('AJAX call placeholder:', options.data.action);
			if (options.success) {
				options.success({ success: true, data: {} });
			}
		}
	};

	/**
	 * 'میرے بچے' پیج شروع کریں
	 */
	function initMyChildren() {
		const rootElement = document.getElementById('bssms-parent-my-children-root');
		if (!rootElement) {
			console.log('My Children root not found. JS exiting.');
			return;
		}

		console.log('Initializing My Children page...');

		// 1. ٹیمپلیٹ ماؤنٹ کریں
		BSSMS_Utils.mountTemplate('bssms-parent-my-children-root', 'bssms-parent-my-children-template');

		// 2. ڈیٹا لوڈ کرنے کے لیے پلیس ہولڈر
		loadChildrenList();

		// 3. ایونٹ ہینڈلرز (Event Handlers)
		setupModalTriggers();
	}

	/**
	 * بچوں کی فہرست (Children List) لوڈ کرنے کا پلیس ہولڈر
	 */
	function loadChildrenList() {
		const gridContainer = document.querySelector('.bssms-children-grid');
		if (!gridContainer) return;

		console.log('AJAX call placeholder: bssms_parent_get_my_children');
		// BSSMS_Utils.wpAjax({ ... });

		// فرضی (mock) ڈیٹا - لے آؤٹ سے ملانے کے لیے کارڈز شامل کریں
		// (نوٹ: پہلا کارڈ ٹیمپلیٹ میں پہلے سے موجود ہے، ہم مزید 3 شامل کریں گے)
		gridContainer.insertAdjacentHTML('beforeend', `
			<div class="bssms-child-card">
				<div class="card-header">
					<img src="" alt="Fatima Khan" class="child-avatar" />
					<div class="child-info">
						<h3><?php _e( 'Fatima Khan', 'bssms' ); ?></h3>
						<span><?php _e( 'Class: 7-B', 'bssms' ); ?></span>
						<span class="status-tag status-active"><?php _e( 'Active', 'bssms' ); ?></span>
					</div>
				</div>
				<div class="card-body">
					<h4>PKR 18,000</h4>
					<span class="sub-label">Total Due</span>
					<ul class="quick-info-list">
						<li><span class="icon-homework"></span>Homework</li>
						<li><span class="icon-fee"></span>2 fees</li>
					</ul>
				</div>
				<div class="card-footer">
					<button class="bssms-btn bssms-btn-primary">View (Pay Now)</button>
					<button class="bssms-btn bssms-btn-secondary">Results (B+)</button>
				</div>
			</div>
			
			<div class="bssms-child-card">
				<div class="card-header">
					<img src="" alt="Faryal Khan" class="child-avatar" />
					<div class="child-info">
						<h3><?php _e( 'Faryal Khan', 'bssms' ); ?></h3>
						<span><?php _e( 'Class: 7-B', 'bssms' ); ?></span>
						<span class="status-tag status-active"><?php _e( 'Active', 'bssms' ); ?></span>
					</div>
				</div>
				<div class="card-body">
					<h4>94%</h4>
					<span class="sub-label">Attendance</span>
					<ul class="quick-info-list">
						<li><span class="icon-transport"></span>Transport Request</li>
						<li><span class="icon-scholar"></span>Day Scholar</li>
					</ul>
				</div>
				<div class="card-footer">
					<button class="bssms-btn bssms-btn-primary">Fees (Pay Now)</button>
					<button class="bssms-btn bssms-btn-secondary">Results (B+)</button>
				</div>
			</div>

			<div class="bssms-child-card">
				<div class="card-header">
					<img src="" alt="Ali Hamza" class="child-avatar" />
					<div class="child-info">
						<h3><?php _e( 'Ali Hamza', 'bssms' ); ?></h3>
						<span><?php _e( 'Class: 7-B', 'bssms' ); ?></span>
						<span class="status-tag status-active"><?php _e( 'Active', 'bssms' ); ?></span>
					</div>
				</div>
				<div class="card-body">
					<h4>24 Nov</h4>
					<span class="sub-label">Next Due Date</span>
					<ul class="quick-info-list">
						<li><span class="icon-transport"></span>Transport Request</li>
						<li><span class="icon-scholar"></span>Day Scholar</li>
					</ul>
				</div>
				<div class="card-footer">
					<button class="bssms-btn bssms-btn-primary">View (Pay Now)</button>
				</div>
			</div>
		`);
	}

	/**
	 * موڈال (Modal) کھولنے اور بند کرنے کے لیے ایونٹس
	 */
	function setupModalTriggers() {
		const modalPlaceholder = document.getElementById('fee-payment-modal-placeholder');
		if (!modalPlaceholder) return;

		// موڈال کھولنے کے لیے بٹنز (مثال کے طور پر)
		// (اصل میں یہ متحرک (dynamically) طور پر لوڈ ہونے والے بٹنز پر سیٹ ہوگا)
		document.body.addEventListener('click', function(e) {
			if (e.target.matches('.bssms-child-card .bssms-btn-primary')) {
				console.log('Opening fee payment modal placeholder...');
				// modalPlaceholder.style.display = 'flex';
			}
			if (e.target.matches('.modal-close-btn') || e.target.matches('.modal-backdrop')) {
				console.log('Closing fee payment modal placeholder...');
				// modalPlaceholder.style.display = 'none';
			}
		});
	}


	// DOM تیار ہونے پر شروع کریں
	document.addEventListener('DOMContentLoaded', initMyChildren);

})();
// 🔴 یہاں پر [Parent My Children JS] ختم ہو رہا ہے

// ✅ Syntax verified block end.
