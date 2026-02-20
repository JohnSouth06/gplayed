document.addEventListener('DOMContentLoaded', () => {
    initCounters();
});

function initCounters() {
    const counters = document.querySelectorAll('.animate-counter');
    const speed = 200;

    const counterObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = +counter.getAttribute('data-target');

                animateValue(counter, 0, target, 1500);

                observer.unobserve(counter);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(counter => {
        counterObserver.observe(counter);
    });
}

function animateValue(obj, start, end, duration) {
    if (end === 0) {
        obj.innerHTML = 0;
        return;
    }

    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);

        obj.innerHTML = Math.floor(end * (1 - Math.pow(1 - progress, 3)));

        if (progress < 1) {
            window.requestAnimationFrame(step);
        } else {
            obj.innerHTML = end; 
        }
    };
    window.requestAnimationFrame(step);
}