$(document).ready(function () {
    let index = 0;
    let events = $(".carrousel-events .event-slide");
    let pointscarrousel = $(".carrousel-points .point");

    function showEvent(i) {
        events.removeClass("active");
        events.eq(i).addClass("active");
        pointscarrousel.removeClass("active");
        pointscarrousel.eq(i).addClass("active");
        index = i;
    }

    $("#suiv").click(function () {
        index++;
        if (index >= events.length) {
            index = 0;
        }
        showEvent(index);
    });

    $("#prev").click(function () {
        index--;
        if (index < 0) {
            index = events.length - 1;
        }
        showEvent(index);
    });

    pointscarrousel.click(function () {
        let numeroPoint = $(this).index();
        showEvent(numeroPoint);
    });

    showEvent(0);

    setInterval(function () {
        index++;

        if (index >= events.length) {
            index = 0;
        }

        showEvent(index);

    }, 4000);

});