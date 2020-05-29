const buttons = document.querySelectorAll(`button[data-modal-trigger]`);
    const facebook_button = document.querySelector(`div#facebook_button`);

for (let button of buttons) {
	modalEvent(button);
}

function modalEvent(button) {

  
	button.addEventListener("click", () => {
		const trigger = button.getAttribute("data-modal-trigger");
		const modal = document.querySelector(`[data-modal=${trigger}]`);
		const contentWrapper = modal.querySelector(".content-wrapper");
		const close = modal.querySelector(".close");

		close.addEventListener("click", () => modal.classList.remove("open"));
		modal.addEventListener("click", () => modal.classList.remove("open"));
		contentWrapper.addEventListener("click", e => e.stopPropagation());

		modal.classList.toggle("open");
	});
}


$('.accordion-item .heading').on('click', function(e) {
    e.preventDefault();

    // Add the correct active class
    if($(this).closest('.accordion-item').hasClass('active')) {
        // Remove active classes
        $('.accordion-item').removeClass('active');
    } else {
        // Remove active classes
        $('.accordion-item').removeClass('active');

        // Add the active class
        $(this).closest('.accordion-item').addClass('active');
    }

    // Show the content
    var $content = $(this).next();
    $content.slideToggle(100);
    $('.accordion-item .content').not($content).slideUp('fast');
});