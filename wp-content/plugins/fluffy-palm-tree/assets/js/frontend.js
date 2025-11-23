(function () {
    const containers = document.querySelectorAll('.fluffy-testimonials');

    containers.forEach((container) => {
        container.querySelectorAll('.fluffy-testimonial').forEach((card) => {
            card.addEventListener('mouseenter', () => card.classList.add('is-active'));
            card.addEventListener('mouseleave', () => card.classList.remove('is-active'));
        });
    });
})();
