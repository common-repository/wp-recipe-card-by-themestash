(function($, window) {
	'use strict';

	// Recipe card items
	jQuery('.wp-block-themestash-recipe-card .ingredients li').on(
		'click',
		function() {
			jQuery(this).toggleClass('selected');
		}
	);

	jQuery('.wp-block-themestash-recipe-card .recipe-step-number').on(
		'click',
		function() {
			jQuery(this)
				.parent()
				.toggleClass('selected');
		}
	);

	jQuery('.wp-block-themestash-recipe-card .recipe-print').on(
		'click',
		function() {
			var content = document.getElementsByClassName(
				'wp-block-themestash-recipe-card'
			)[0].innerHTML;

			console.log(
				document.getElementsByClassName('wp-block-themestash-recipe-card')[0]
					.innerHTML
			);

			var mywindow = window.open('', 'PRINT', 'height=400,width=600');

			mywindow.document.write(
				'<html><head><title>' + document.title + '</title>'
			);
			mywindow.document.write('</head><body >');
			mywindow.document.write('<h1>' + document.title + '</h1>');
			mywindow.document.write(content);
			mywindow.document.write(
				'<style>@media print {.recipe-details{display: table;.recipe-details > div{display: table-cell;}}}</style>'
			);
			mywindow.document.write('</body></html>');

			mywindow.document.close(); // necessary for IE >= 10
			mywindow.focus(); // necessary for IE >= 10*/

			mywindow.print();
			mywindow.close();

			return true;
		}
	);
})(jQuery, this);
