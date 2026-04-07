jQuery(function ($) {
	// Confirm delete single entry
	$(document).on('click', '.aodn-cl-delete-link', function (e) {
		if (!confirm(aodnCL.confirmDelete)) {
			e.preventDefault();
		}
	});

	// Confirm purge all
	$('#aodn-cl-purge').on('click', function (e) {
		if (!confirm(aodnCL.confirmPurge)) {
			e.preventDefault();
		}
	});
});
