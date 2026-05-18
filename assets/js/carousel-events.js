(function ($) {
    'use strict';

    $(function () {
        var $carousel = $('.carrousel-events');
        var $slides = $carousel.find('.event-slide');
        var $points = $('.carrousel-points .point');
        var $prev = $('#prev');
        var $next = $('#suiv');
        var currentIndex = 0;
        var intervalId = null;
        var delay = 4000;

        if (!$carousel.length || !$slides.length) {
            return;
        }

        function showEvent(index) {
            if (!$slides.length) {
                return;
            }

            if (index < 0) {
                index = $slides.length - 1;
            }

            if (index >= $slides.length) {
                index = 0;
            }

            $slides.removeClass('active').attr('aria-hidden', 'true');
            $slides.eq(index).addClass('active').attr('aria-hidden', 'false');

            $points.removeClass('active').attr('aria-current', 'false');
            $points.eq(index).addClass('active').attr('aria-current', 'true');

            currentIndex = index;
        }

        function nextEvent() {
            showEvent(currentIndex + 1);
        }

        function prevEvent() {
            showEvent(currentIndex - 1);
        }

        function startAutoPlay() {
            stopAutoPlay();
            if ($slides.length > 1) {
                intervalId = window.setInterval(nextEvent, delay);
            }
        }

        function stopAutoPlay() {
            if (intervalId) {
                window.clearInterval(intervalId);
                intervalId = null;
            }
        }

        $next.on('click', function () {
            nextEvent();
            startAutoPlay();
        });

        $prev.on('click', function () {
            prevEvent();
            startAutoPlay();
        });

        $points.on('click', function () {
            showEvent($(this).index());
            startAutoPlay();
        });

        $carousel.on('mouseenter focusin', stopAutoPlay);
        $carousel.on('mouseleave focusout', startAutoPlay);

        showEvent(0);
        startAutoPlay();
    });
})(jQuery);
