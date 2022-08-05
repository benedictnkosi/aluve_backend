$(document).ready(function () {

    sessionStorage.setItem("property_uid", getUrlParameter('uid'))
    $("#new-res-form").submit(function (event) {
        event.preventDefault();
    });

    $("#new-res-form").validate({
        // Specify validation rules
        rules: {
            guestName: "required",
            phoneNumber: "required",
            email: {
                required: false,
                email: true
            }
        },
        submitHandler: function () {
            createReservation();
        }
    });

    $("body").addClass("loading");

    let date = new Date();
    let endDate = new Date(date.getTime());
    //get available rooms for today if previous date not set
    if (sessionStorage.getItem("checkInDate") == null) {
        endDate.setDate(date.getDate() + 1);
        const strToday = date.getFullYear() + "-" + (date.getMonth() + 1) + "-" + date.getDate();
        const strTomorrow = endDate.getFullYear() + "-" + (endDate.getMonth() + 1) + "-" + endDate.getDate();
        sessionStorage.setItem('checkInDate', strToday);
        sessionStorage.setItem('checkOutDate', strTomorrow);
        getAvailableRooms(strToday, strTomorrow);
    } else {
        date = new Date(sessionStorage.getItem('checkInDate'));
        endDate = new Date(sessionStorage.getItem('checkOutDate'));
        $(this).val(sessionStorage.getItem('checkInDate') + ' - ' + sessionStorage.getItem('checkOutDate'));
        getAvailableRooms(sessionStorage.getItem('checkInDate'), sessionStorage.getItem('checkOutDate'));
    }

    //date picker
    $.getScript("https://cdn.jsdelivr.net/jquery/latest/jquery.min.js", function () {
        $.getScript("https://cdn.jsdelivr.net/momentjs/latest/moment.min.js", function () {
            $.getScript("https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js", function () {

                $('#checkindate').daterangepicker({
                    opens: 'left',
                    autoApply: false,
                    minDate: date
                }, function (start, end, label) {
                    console.log("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
                });

                $('#checkindate').daterangepicker({startDate: date, endDate: endDate});

                $('#checkindate').on('apply.daterangepicker', function (event, picker) {
                    getAvailableRooms(picker.startDate.format("YYYY-MM-DD"), picker.endDate.format("YYYY-MM-DD"));
                    sessionStorage.setItem('checkInDate', picker.startDate.format("YYYY-MM-DD"));
                    sessionStorage.setItem('checkOutDate', picker.endDate.format("YYYY-MM-DD"));

                    let checkInDate = new Date(picker.startDate.format("YYYY-MM-DD"));
                    let checkOutDate = new Date(picker.endDate.format("YYYY-MM-DD"))
                    let difference = checkOutDate - checkInDate;
                    let totalDays = Math.ceil(difference / (1000 * 3600 * 24));
                    console.log("date diff is " + totalDays);
                    sessionStorage.setItem('numberOfNights', totalDays);
                });
            });
        });
    });

    showBackToReservationsLink();
});


function isEmail(email) {
    var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    return regex.test(email);
}

function displayTotal() {
    let numberOfNights = parseInt(sessionStorage.getItem('numberOfNights'));
    let total = 0;
    let nightsMessage = "";
    let roomIdArray = [];
    //find all rooms added
    var buttons = document.getElementsByTagName("button");
    for (var i = 0; i < buttons.length; i++) {
        if (buttons[i].textContent === "Remove") {
            roomId = buttons[i].getAttribute("data-roomId");
            roomName = buttons[i].getAttribute("data-roomName");
            roomPrice = buttons[i].getAttribute("data-roomPrice");
            total += (numberOfNights * parseInt(roomPrice));
            nightsMessage += roomName + " - " + numberOfNights + " x nights @ R" + roomPrice + ".00" + "<br>";
            roomIdArray.push(roomId);
        }

    }

    if (total > 0) {
        sessionStorage.setItem("isRoomSelected", "yes");
    } else {
        sessionStorage.removeItem("isRoomSelected");
    }
    sessionStorage.setItem("selected_rooms_array", JSON.stringify(roomIdArray));

    let totalMessage = "Total: R" + total + ".00";

    sessionStorage.setItem("item_description", nightsMessage);
    sessionStorage.setItem("item_amount", total.toString());

    $('#total_message').html(totalMessage);
    $('#nights_message').html(nightsMessage);
}

function getAvailableRooms(checkInDate, checkOutDate) {
    let url = "/public/availablerooms/" + checkInDate + "/" + checkOutDate  + "/" + sessionStorage.getItem("property_uid");
    $.getJSON(url + "?callback=?", null, function (data) {
        let roomIndex;
        $("#availableRoomsDropdown").html(data.html);
        const roomArray = [];
        const roomIdsArray = [];
        let room_id = "";
        $('.vodiapicker option').each(function () {
            const img = $(this).attr("data-thumbnail");
            const price = $(this).attr("data-price");
            const room_id = $(this).attr("data-roomId");
            const sleeps = $(this).attr("data-sleeps");
            const beds = $(this).attr("data-beds");
            const room_name = this.innerText;

            const bedArray = beds.split(",");
            let bedshtml = "";

            if (beds.length !== 0) {
                bedArray.forEach(bed => bedshtml += '<span class="fa fa-bed">' + bed + '</span>');
            }

            if (price.localeCompare("0") === 0) {
                var item = '<li><img src="' + img + '" data-price="' + price + '" data-roomId="' + room_id + '" data-roomName="' + room_name + '"/><div class="div-select-room-name">' + room_name + '<div class="select_sleeps"><span>ZAR ' + price + '</span><span class="fa fa-users">' + sleeps + ' Guests</span>'+bedshtml+'</div></div>' +
                    '</li>';
            } else {
                var item = '<li><img src="' + img + '" data-price="' + price + '" data-roomId="' + room_id + '" data-roomName="' + room_name + '"/><div class="div-select-room-name">' + room_name + '<div class="select_sleeps"><span>ZAR ' + price + '</span><span class="fa fa-users">' + sleeps + ' Guests</span>'+bedshtml+'</div><button class="btn btn-style btn-secondary book mt-3 add-room-button" data-roomId="' + room_id + '" data-roomName="' + room_name + '" data-roomPrice="' + price + '">Add</button></div>' +
                    '</li>';
            }

            roomArray.push(item);
            roomIdsArray.push(room_id);
        })

        $('#a').html(roomArray);

//Set the button value to the first el of the array

        $(".b").css("display", "block");

        //check local storage for the lang
        const roomId = getUrlParameter("id");
        if (roomId) {
            //find an item with value of roomId
            roomIndex = roomIdsArray.indexOf(roomId);
            $('.btn-select').html(roomArray[roomIndex]);
            $('.btn-select').attr('value', roomId);
        } else {
            roomIndex = roomArray.indexOf('ch');
            console.log(roomIndex);
            $('.btn-select').html(roomArray[roomIndex]);
            $('.btn-select').attr('value', roomId);
        }
        let checkInDateDate = new Date(checkInDate);
        let checkOutDateDate = new Date(checkOutDate)
        let difference = checkOutDateDate - checkInDateDate;
        let totalDays = Math.ceil(difference / (1000 * 3600 * 24));
        console.log("date diff is " + totalDays);
        sessionStorage.setItem('numberOfNights', totalDays);

        if (room_id.localeCompare("0") !== 0) {
            displayTotal();
        }

        $(".add-room-button").click(function (event) {
            event.preventDefault();
            if ($(this).text().localeCompare("Remove") === 0) {
                $(this).text("Add");
            } else {
                $(this).text("Remove");
            }

            displayTotal();
        });

        $("body").removeClass("loading");
    });
}

function createReservation() {
    $("#reservation_error_message_div").addClass("display-none");
    let isRoomSelected;
    if ($('#accept_terms').is(':checked')) {
        $("#reservation_error_message_div").addClass("display-none");
        const guestName = $('#guestName').val();
        const phoneNumber = $('#phoneNumber').val().trim().replaceAll(" ", "");
        const email = $('#email').val();
        const checkInDate = sessionStorage.getItem('checkInDate');
        const checkOutDate = sessionStorage.getItem('checkOutDate');

        isRoomSelected = sessionStorage.getItem("isRoomSelected");
        if (isRoomSelected === null) {
            $("#reservation_message").text("Please select a room")
            $("#reservation_error_message_div").removeClass("display-none");
            return;
        }

        $("body").addClass("loading");
        let url = "/public/reservations/create/" + sessionStorage.getItem("selected_rooms_array") + '/' + guestName + '/' + phoneNumber + '/' + checkInDate + '/' + checkOutDate;
        if(email.length > 0){
            url += "/" + email;
        }
        $.getJSON(url + "?callback=?", null, function (data) {
            $("body").removeClass("loading");
            if (data[0].result_code !== 0) {
                $("#reservation_message").text(data[0].result_message)
                $("#reservation_error_message_div").removeClass("display-none");
            } else {
                sessionStorage.setItem("reservation_id", JSON.stringify(data[0].reservation_id));
                window.location.href = "/confirmation";
            }
        }).done(function() { $("body").removeClass("loading"); })
            .fail(function() {
                $("#reservation_message").text("Server error occurred, please try again")
                $("#reservation_error_message_div").removeClass("display-none");
            })
            .always(function() { $("body").removeClass("loading"); });

    } else {
        $("#reservation_message").text("Please accept the terms and conditions")
        $("#reservation_error_message_div").removeClass("display-none");
    }
}
