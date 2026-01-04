/**
 * Booking Actions JavaScript
 * Handles modify and cancel booking functionality
 */

let currentBooking = null;

/**
 * View QR Code
 */
function viewQRCode(bookingId) {
    window.location.href = 'qr-code.php?booking_id=' + bookingId;
}

/**
 * View Booking Details
 */
function viewDetails(bookingId) {
    window.location.href = 'booking-details.php?booking_id=' + bookingId;
}

/**
 * Open Modify Booking Modal
 */
function openModifyModal(booking) {
    currentBooking = booking;
    
    const content = `
        <div class="booking-info-summary" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
            <h6 style="font-family: 'Playfair Display', serif; color: var(--primary-brown); margin-bottom: 15px;">
                <i class="fas fa-info-circle"></i> Current Booking Details
            </h6>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Booking Reference:</strong> ${booking.booking_reference}</p>
                    <p><strong>Room:</strong> ${booking.room_name}</p>
                    <p><strong>Room Type:</strong> ${booking.room_type}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Check-in:</strong> ${formatDate(booking.check_in_date)}</p>
                    <p><strong>Check-out:</strong> ${formatDate(booking.check_out_date)}</p>
                    <p><strong>Guests:</strong> ${booking.num_adults} Adults, ${booking.num_children} Children</p>
                </div>
            </div>
            <p style="margin-top: 10px;"><strong>Total Amount:</strong> <span style="color: var(--primary-brown); font-size: 18px; font-weight: 700;">$${parseFloat(booking.total_amount).toFixed(2)}</span></p>
        </div>

        <div class="alert alert-info">
            <i class="fas fa-clock"></i> <strong>Modification Policy:</strong> Changes must be made at least 24 hours before check-in.
        </div>

        <div class="modification-options">
            <h6 style="font-family: 'Playfair Display', serif; color: var(--primary-brown); margin-bottom: 20px;">
                What would you like to modify?
            </h6>
            
            <div class="list-group">
                <button type="button" class="list-group-item list-group-item-action" onclick="showModifyDatesForm()">
                    <i class="fas fa-calendar-alt" style="color: var(--primary-brown); margin-right: 10px;"></i>
                    <strong>Change Dates</strong>
                    <p class="mb-0 text-muted" style="font-size: 13px;">Modify your check-in and check-out dates</p>
                </button>
                
                <button type="button" class="list-group-item list-group-item-action" onclick="showModifyRoomForm()">
                    <i class="fas fa-bed" style="color: var(--primary-brown); margin-right: 10px;"></i>
                    <strong>Change Room</strong>
                    <p class="mb-0 text-muted" style="font-size: 13px;">Switch to a different room type</p>
                </button>
                
                <button type="button" class="list-group-item list-group-item-action" onclick="showModifyGuestsForm()">
                    <i class="fas fa-users" style="color: var(--primary-brown); margin-right: 10px;"></i>
                    <strong>Change Guest Count</strong>
                    <p class="mb-0 text-muted" style="font-size: 13px;">Update number of adults and children</p>
                </button>
            </div>
        </div>
    `;
    
    document.getElementById('modifyBookingContent').innerHTML = content;
    $('#modifyBookingModal').modal('show');
}

/**
 * Show Modify Dates Form
 */
function showModifyDatesForm() {
    const content = `
        <div class="modify-form">
            <button class="btn btn-sm btn-link" onclick="openModifyModal(currentBooking)" style="padding: 0; margin-bottom: 15px;">
                <i class="fas fa-arrow-left"></i> Back to options
            </button>
            
            <h6 style="font-family: 'Playfair Display', serif; color: var(--primary-brown); margin-bottom: 20px;">
                <i class="fas fa-calendar-alt"></i> Change Booking Dates
            </h6>
            
            <form id="modifyDatesForm">
                <div class="form-group">
                    <label>New Check-in Date</label>
                    <input type="date" class="form-control" id="newCheckIn" required 
                           min="${getTodayDate()}" value="${currentBooking.check_in_date}">
                </div>
                
                <div class="form-group">
                    <label>New Check-out Date</label>
                    <input type="date" class="form-control" id="newCheckOut" required 
                           min="${getTomorrowDate()}" value="${currentBooking.check_out_date}">
                </div>
                
                <div id="dateChangeInfo" class="alert alert-warning" style="display: none;">
                    <i class="fas fa-info-circle"></i> <span id="dateChangeMessage"></span>
                </div>
                
                <div class="text-right">
                    <button type="button" class="btn btn-secondary" onclick="openModifyModal(currentBooking)">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Confirm Date Change
                    </button>
                </div>
            </form>
        </div>
    `;
    
    document.getElementById('modifyBookingContent').innerHTML = content;
    
    // Add form submit handler
    document.getElementById('modifyDatesForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitDateModification();
    });
    
    // Add date change listeners
    document.getElementById('newCheckIn').addEventListener('change', calculateDateChange);
    document.getElementById('newCheckOut').addEventListener('change', calculateDateChange);
}

/**
 * Show Modify Room Form
 */
function showModifyRoomForm() {
    const content = `
        <div class="modify-form">
            <button class="btn btn-sm btn-link" onclick="openModifyModal(currentBooking)" style="padding: 0; margin-bottom: 15px;">
                <i class="fas fa-arrow-left"></i> Back to options
            </button>
            
            <h6 style="font-family: 'Playfair Display', serif; color: var(--primary-brown); margin-bottom: 20px;">
                <i class="fas fa-bed"></i> Change Room
            </h6>
            
            <div id="availableRoomsLoading" class="text-center" style="padding: 40px;">
                <i class="fas fa-spinner fa-spin fa-3x" style="color: var(--primary-brown);"></i>
                <p style="margin-top: 15px;">Loading available rooms...</p>
            </div>
            
            <div id="availableRoomsList" style="display: none;"></div>
        </div>
    `;
    
    document.getElementById('modifyBookingContent').innerHTML = content;
    loadAvailableRooms();
}

/**
 * Show Modify Guests Form
 */
function showModifyGuestsForm() {
    const content = `
        <div class="modify-form">
            <button class="btn btn-sm btn-link" onclick="openModifyModal(currentBooking)" style="padding: 0; margin-bottom: 15px;">
                <i class="fas fa-arrow-left"></i> Back to options
            </button>
            
            <h6 style="font-family: 'Playfair Display', serif; color: var(--primary-brown); margin-bottom: 20px;">
                <i class="fas fa-users"></i> Change Guest Count
            </h6>
            
            <form id="modifyGuestsForm">
                <div class="form-group">
                    <label>Number of Adults</label>
                    <select class="form-control" id="newAdults" required>
                        ${generateOptions(1, 10, currentBooking.num_adults)}
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Number of Children</label>
                    <select class="form-control" id="newChildren" required>
                        ${generateOptions(0, 8, currentBooking.num_children)}
                    </select>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Current room capacity will be checked automatically.
                </div>
                
                <div class="text-right">
                    <button type="button" class="btn btn-secondary" onclick="openModifyModal(currentBooking)">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Confirm Guest Change
                    </button>
                </div>
            </form>
        </div>
    `;
    
    document.getElementById('modifyBookingContent').innerHTML = content;
    
    // Add form submit handler
    document.getElementById('modifyGuestsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitGuestModification();
    });
}

/**
 * Open Cancel Booking Modal
 */
function openCancelModal(booking) {
    currentBooking = booking;
    
    // Calculate refund eligibility
    const checkInDate = new Date(booking.check_in_date);
    const today = new Date();
    const daysUntilCheckIn = Math.floor((checkInDate - today) / (1000 * 60 * 60 * 24));
    const isRefundable = daysUntilCheckIn >= 7;
    const refundPercentage = isRefundable ? 100 : 0;
    const refundAmount = (parseFloat(booking.total_amount) * refundPercentage) / 100;
    
    const content = `
        <div class="booking-info-summary" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
            <h6 style="font-family: 'Playfair Display', serif; color: #e74c3c; margin-bottom: 15px;">
                <i class="fas fa-exclamation-triangle"></i> Booking to Cancel
            </h6>
            <p><strong>Booking Reference:</strong> ${booking.booking_reference}</p>
            <p><strong>Room:</strong> ${booking.room_name} (${booking.room_type})</p>
            <p><strong>Check-in:</strong> ${formatDate(booking.check_in_date)}</p>
            <p><strong>Check-out:</strong> ${formatDate(booking.check_out_date)}</p>
            <p><strong>Total Amount:</strong> <span style="font-size: 18px; font-weight: 700;">$${parseFloat(booking.total_amount).toFixed(2)}</span></p>
        </div>

        <div class="alert ${isRefundable ? 'alert-success' : 'alert-warning'}">
            <h6 style="margin-bottom: 10px;"><i class="fas fa-info-circle"></i> Cancellation Policy</h6>
            <p style="margin-bottom: 10px;"><strong>Free cancellation up to 7 days before check-in</strong></p>
            <p style="margin-bottom: 10px;">Days until check-in: <strong>${daysUntilCheckIn} days</strong></p>
            <p style="margin-bottom: 0;">
                ${isRefundable 
                    ? `<i class="fas fa-check-circle"></i> <strong>Refund Amount: $${refundAmount.toFixed(2)} (${refundPercentage}%)</strong>` 
                    : `<i class="fas fa-times-circle"></i> <strong>Non-refundable (within 7 days of check-in)</strong>`
                }
            </p>
        </div>

        <form id="cancelBookingForm">
            <div class="form-group">
                <label><strong>Reason for Cancellation</strong> <span style="color: red;">*</span></label>
                <textarea class="form-control" id="cancellationReason" rows="4" required 
                          placeholder="Please tell us why you're cancelling this booking..."></textarea>
            </div>
            
            <div class="form-check" style="margin-bottom: 20px;">
                <input type="checkbox" class="form-check-input" id="confirmCancel" required>
                <label class="form-check-label" for="confirmCancel">
                    I understand that ${isRefundable ? 'a refund will be processed within 5-7 business days' : 'this booking is non-refundable'}
                </label>
            </div>
            
            <div class="text-right">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Keep Booking</button>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-times-circle"></i> Confirm Cancellation
                </button>
            </div>
        </form>
    `;
    
    document.getElementById('cancelBookingContent').innerHTML = content;
    
    // Add form submit handler
    document.getElementById('cancelBookingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitCancellation();
    });
    
    $('#cancelBookingModal').modal('show');
}

/**
 * Submit Date Modification
 */
function submitDateModification() {
    const newCheckIn = document.getElementById('newCheckIn').value;
    const newCheckOut = document.getElementById('newCheckOut').value;
    
    showLoading('Processing date change...');
    
    fetch('../api/modify-booking-advanced.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            booking_id: currentBooking.booking_id,
            modification_type: 'dates',
            new_check_in: newCheckIn,
            new_check_out: newCheckOut
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showSuccess(data.message, data.data);
            setTimeout(() => location.reload(), 2000);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        hideLoading();
        showError('Network error: ' + error.message);
    });
}

/**
 * Submit Guest Modification
 */
function submitGuestModification() {
    const newAdults = parseInt(document.getElementById('newAdults').value);
    const newChildren = parseInt(document.getElementById('newChildren').value);
    
    showLoading('Updating guest count...');
    
    fetch('../api/modify-booking-advanced.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            booking_id: currentBooking.booking_id,
            modification_type: 'guests',
            new_adults: newAdults,
            new_children: newChildren
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showSuccess(data.message);
            setTimeout(() => location.reload(), 2000);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        hideLoading();
        showError('Network error: ' + error.message);
    });
}

/**
 * Submit Cancellation
 */
function submitCancellation() {
    const reason = document.getElementById('cancellationReason').value;
    
    showLoading('Processing cancellation...');
    
    fetch('../api/cancel-booking-advanced.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            booking_id: currentBooking.booking_id,
            cancellation_reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            $('#cancelBookingModal').modal('hide');
            showSuccess(data.message, data.data);
            setTimeout(() => location.reload(), 3000);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        hideLoading();
        showError('Network error: ' + error.message);
    });
}

/**
 * Load Available Rooms for Modification
 */
function loadAvailableRooms() {
    const totalGuests = parseInt(currentBooking.num_adults) + parseInt(currentBooking.num_children);

    fetch(`../api/get-available-rooms-for-modification.php?booking_id=${currentBooking.booking_id}&check_in=${currentBooking.check_in_date}&check_out=${currentBooking.check_out_date}&num_guests=${totalGuests}`)
    .then(response => response.json())
    .then(data => {
        document.getElementById('availableRoomsLoading').style.display = 'none';

        if (data.success && data.data.rooms.length > 0) {
            displayAvailableRooms(data.data.rooms);
        } else {
            document.getElementById('availableRoomsList').innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> No alternative rooms available for your dates.
                </div>
            `;
            document.getElementById('availableRoomsList').style.display = 'block';
        }
    })
    .catch(error => {
        document.getElementById('availableRoomsLoading').style.display = 'none';
        document.getElementById('availableRoomsList').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-times-circle"></i> Error loading rooms: ${error.message}
            </div>
        `;
        document.getElementById('availableRoomsList').style.display = 'block';
    });
}

/**
 * Display Available Rooms
 */
function displayAvailableRooms(rooms) {
    let html = '<div class="available-rooms-list">';

    rooms.forEach(room => {
        const priceDiff = room.price_difference;
        const priceChangeText = priceDiff > 0
            ? `<span style="color: #e74c3c;">+$${Math.abs(priceDiff).toFixed(2)}</span>`
            : (priceDiff < 0
                ? `<span style="color: #27ae60;">-$${Math.abs(priceDiff).toFixed(2)}</span>`
                : '<span style="color: #95a5a6;">No change</span>');

        html += `
            <div class="room-option" style="border: 2px solid ${room.is_current_room ? '#3498db' : '#ddd'}; border-radius: 8px; padding: 15px; margin-bottom: 15px; ${room.is_current_room ? 'background: #e3f2fd;' : ''}">
                <div class="row">
                    <div class="col-md-3">
                        <img src="../${room.room_image}" alt="${room.room_name}" style="width: 100%; border-radius: 6px;">
                    </div>
                    <div class="col-md-6">
                        <h6 style="font-family: 'Playfair Display', serif; color: var(--primary-brown); margin-bottom: 5px;">
                            ${room.room_name} ${room.is_current_room ? '<span class="badge badge-primary">Current</span>' : ''}
                        </h6>
                        <p style="margin-bottom: 5px; font-size: 13px;"><i class="fas fa-door-open"></i> ${room.room_type} | Room ${room.room_number}</p>
                        <p style="margin-bottom: 5px; font-size: 13px;"><i class="fas fa-users"></i> Max ${room.max_occupancy} guests</p>
                        <p style="margin-bottom: 0; font-size: 13px;"><i class="fas fa-bed"></i> ${room.bed_type}</p>
                    </div>
                    <div class="col-md-3 text-right">
                        <p style="font-size: 18px; font-weight: 700; color: var(--primary-brown); margin-bottom: 5px;">
                            $${parseFloat(room.total_price).toFixed(2)}
                        </p>
                        <p style="font-size: 13px; margin-bottom: 10px;">
                            Price change: ${priceChangeText}
                        </p>
                        ${!room.is_current_room ? `
                            <button class="btn btn-sm btn-primary" onclick="selectNewRoom(${room.room_id}, '${room.room_name}', ${room.total_price}, ${room.price_difference})">
                                <i class="fas fa-check"></i> Select
                            </button>
                        ` : '<span class="text-muted">Current Room</span>'}
                    </div>
                </div>
            </div>
        `;
    });

    html += '</div>';

    document.getElementById('availableRoomsList').innerHTML = html;
    document.getElementById('availableRoomsList').style.display = 'block';
}

/**
 * Select New Room
 */
function selectNewRoom(roomId, roomName, totalPrice, priceDiff) {
    if (!confirm(`Change to ${roomName}?\n\nNew total: $${totalPrice.toFixed(2)}\nPrice difference: ${priceDiff >= 0 ? '+' : ''}$${priceDiff.toFixed(2)}`)) {
        return;
    }

    showLoading('Changing room...');

    fetch('../api/modify-booking-advanced.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            booking_id: currentBooking.booking_id,
            modification_type: 'room',
            new_room_id: roomId
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            $('#modifyBookingModal').modal('hide');
            showSuccess(data.message, data.data);
            setTimeout(() => location.reload(), 2000);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        hideLoading();
        showError('Network error: ' + error.message);
    });
}

/**
 * Calculate Date Change Impact
 */
function calculateDateChange() {
    const newCheckIn = document.getElementById('newCheckIn').value;
    const newCheckOut = document.getElementById('newCheckOut').value;

    if (!newCheckIn || !newCheckOut) return;

    const checkInDate = new Date(newCheckIn);
    const checkOutDate = new Date(newCheckOut);
    const nights = Math.floor((checkOutDate - checkInDate) / (1000 * 60 * 60 * 24));

    if (nights <= 0) {
        document.getElementById('dateChangeInfo').style.display = 'block';
        document.getElementById('dateChangeMessage').textContent = 'Check-out must be after check-in';
        return;
    }

    const pricePerNight = parseFloat(currentBooking.price_per_night);
    const newTotal = pricePerNight * nights;
    const oldTotal = parseFloat(currentBooking.total_amount);
    const priceDiff = newTotal - oldTotal;

    document.getElementById('dateChangeInfo').style.display = 'block';
    document.getElementById('dateChangeInfo').className = 'alert alert-info';
    document.getElementById('dateChangeMessage').innerHTML = `
        <strong>New stay:</strong> ${nights} night(s)<br>
        <strong>New total:</strong> $${newTotal.toFixed(2)}<br>
        <strong>Price change:</strong> ${priceDiff >= 0 ? '+' : ''}$${priceDiff.toFixed(2)}
    `;
}

/**
 * Helper Functions
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function getTodayDate() {
    return new Date().toISOString().split('T')[0];
}

function getTomorrowDate() {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    return tomorrow.toISOString().split('T')[0];
}

function generateOptions(min, max, selected) {
    let html = '';
    for (let i = min; i <= max; i++) {
        html += `<option value="${i}" ${i == selected ? 'selected' : ''}>${i}</option>`;
    }
    return html;
}

function showLoading(message) {
    const loadingHtml = `
        <div id="loadingOverlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; display: flex; align-items: center; justify-content: center;">
            <div style="background: white; padding: 30px; border-radius: 10px; text-align: center;">
                <i class="fas fa-spinner fa-spin fa-3x" style="color: var(--primary-brown); margin-bottom: 15px;"></i>
                <p style="margin: 0; font-weight: 600;">${message}</p>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', loadingHtml);
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) overlay.remove();
}

function showSuccess(message, data = null) {
    let html = `
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 10000; min-width: 300px;">
            <strong><i class="fas fa-check-circle"></i> Success!</strong><br>
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', html);
}

function showError(message) {
    let html = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 10000; min-width: 300px;">
            <strong><i class="fas fa-times-circle"></i> Error!</strong><br>
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', html);
}

