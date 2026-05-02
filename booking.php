<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Book Appointment — Appoint</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<button class="hamburger" onclick="toggleSidebar()"><span></span><span></span><span></span></button>
<div class="app-layout">
  <?php include 'includes/sidebar.php'; ?>
  <main class="main-content">
    <div class="topbar">
      <span class="topbar-title">Book an Appointment</span>
      <div class="topbar-actions">
        <a href="dashboard.php" class="btn-secondary">← Back to Dashboard</a>
      </div>
    </div>

    <div class="page-content">
      <!-- Step Indicator -->
      <div class="steps-indicator mb-8">
        <div class="step active" id="step1ind"><span class="step-num">1</span><span class="step-label">Choose Service</span></div>
        <div class="step-line"></div>
        <div class="step" id="step2ind"><span class="step-num">2</span><span class="step-label">Select Date & Time</span></div>
        <div class="step-line"></div>
        <div class="step" id="step3ind"><span class="step-num">3</span><span class="step-label">Confirm</span></div>
      </div>

      <div class="booking-grid">
        <!-- LEFT: Steps -->
        <div>
          <!-- STEP 1: Services -->
          <div id="stepServices">
            <div class="section-title">Select a Service</div>
            <p class="section-sub">Choose the service you'd like to book.</p>
            <div class="services-grid" id="servicesGrid">
              <div class="text-mute">Loading services...</div>
            </div>
          </div>

          <!-- STEP 2: Calendar + Slots -->
          <div id="stepCalendar" style="display:none;">
            <div class="flex items-center justify-between mb-4">
              <div>
                <div class="section-title">Choose a Date</div>
                <p class="section-sub">Select a date then pick a time slot.</p>
              </div>
              <button onclick="goToStep(1)" class="btn-secondary" style="font-size:0.8rem;padding:7px 14px;">← Change Service</button>
            </div>
            <div class="calendar-wrapper mb-6">
              <div id="bookingCalendar"></div>
            </div>
            <div id="slotsSection" style="display:none;">
              <div class="section-title" id="slotsDateLabel">Available Times</div>
              <p class="section-sub">All times are local. Each slot is available once.</p>
              <div class="time-slots" id="timeSlotGrid">
                <div class="text-mute">Loading slots...</div>
              </div>
            </div>
          </div>

          <!-- STEP 3: Confirm -->
          <div id="stepConfirm" style="display:none;">
            <div class="flex items-center justify-between mb-6">
              <div>
                <div class="section-title">Confirm Your Booking</div>
                <p class="section-sub">Review the details below and add any notes.</p>
              </div>
              <button onclick="goToStep(2)" class="btn-secondary" style="font-size:0.8rem;padding:7px 14px;">← Change Time</button>
            </div>
            <div class="card mb-6">
              <div class="card-body">
                <div class="summary-item">
                  <span class="summary-label">Service</span>
                  <span class="summary-value" id="confirmService">—</span>
                </div>
                <div class="summary-item">
                  <span class="summary-label">Date</span>
                  <span class="summary-value" id="confirmDate">—</span>
                </div>
                <div class="summary-item">
                  <span class="summary-label">Time</span>
                  <span class="summary-value" id="confirmTime">—</span>
                </div>
                <div class="summary-item">
                  <span class="summary-label">Duration</span>
                  <span class="summary-value" id="confirmDuration">—</span>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label>Additional Notes (Optional)</label>
              <textarea id="bookingNotes" rows="3" placeholder="Any special requests or information..."></textarea>
            </div>
            <div id="bookingError" class="form-error"></div>
            <button class="btn-primary gold" onclick="confirmBooking()" id="confirmBtn" style="width:100%;margin-top:8px;">
              <span>Confirm Booking</span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </button>
          </div>

          <!-- STEP 4: Success -->
          <div id="stepSuccess" style="display:none;">
            <div class="card">
              <div class="confirm-success">
                <div class="confirm-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <h2 class="font-display" style="font-size:1.8rem;font-weight:300;margin-bottom:12px;">Booking Confirmed!</h2>
                <p class="text-mute mb-4">Your appointment has been booked. A confirmation email has been sent to your inbox.</p>
                <div class="email-booking-box" style="text-align:left;max-width:360px;margin:0 auto 28px;">
                  <div class="email-booking-row">
                    <span class="email-booking-label">Reference</span>
                    <span class="email-booking-val" id="successRef">—</span>
                  </div>
                  <div class="email-booking-row">
                    <span class="email-booking-label">Service</span>
                    <span class="email-booking-val" id="successService">—</span>
                  </div>
                  <div class="email-booking-row">
                    <span class="email-booking-label">Date & Time</span>
                    <span class="email-booking-val" id="successDateTime">—</span>
                  </div>
                </div>
                <div class="flex gap-3" style="justify-content:center;">
                  <a href="bookings.php" class="btn-primary">View My Bookings</a>
                  <a href="booking.php" class="btn-secondary">Book Another</a>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT: Summary -->
        <div>
          <div class="booking-summary">
            <div class="summary-header">
              <h3>Booking Summary</h3>
              <p style="font-size:0.8rem;color:rgba(255,255,255,0.5);margin-top:4px;">Review your selections</p>
            </div>
            <div class="summary-body">
              <div class="summary-item">
                <span class="summary-label">Service</span>
                <span class="summary-value" id="sumService">Not selected</span>
              </div>
              <div class="summary-item">
                <span class="summary-label">Duration</span>
                <span class="summary-value" id="sumDuration">—</span>
              </div>
              <div class="summary-item">
                <span class="summary-label">Date</span>
                <span class="summary-value" id="sumDate">Not selected</span>
              </div>
              <div class="summary-item">
                <span class="summary-label">Time</span>
                <span class="summary-value" id="sumTime">Not selected</span>
              </div>
              <div class="summary-item summary-total">
                <span class="summary-label">Total</span>
                <span class="summary-value" id="sumPrice">$0.00</span>
              </div>
            </div>
          </div>

          <!-- Help card -->
          <div class="card mt-4" style="margin-top:16px;">
            <div class="card-body">
              <div style="font-size:0.82rem;color:var(--ink-soft);line-height:1.7;">
                <strong>Booking Policy</strong><br>
                Cancellations must be made 24h in advance. Rescheduling available up to 2 hours before your appointment.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<div class="toast-container" id="toastContainer"></div>

<style>
.steps-indicator { display:flex; align-items:center; gap:0; }
.step { display:flex; align-items:center; gap:10px; }
.step-num { width:32px; height:32px; border-radius:50%; border:2px solid var(--border); display:flex; align-items:center; justify-content:center; font-size:0.82rem; font-weight:600; color:var(--ink-mute); background:var(--paper-card); transition:var(--transition); }
.step-label { font-size:0.82rem; color:var(--ink-mute); font-weight:500; transition:var(--transition); }
.step.active .step-num { border-color:var(--gold); background:var(--gold); color:var(--ink); }
.step.active .step-label { color:var(--ink); }
.step.done .step-num { border-color:var(--success); background:var(--success); color:#fff; }
.step-line { flex:1; height:2px; background:var(--border); margin:0 12px; min-width:40px; }
</style>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.js"></script>
<script src="js/app.js"></script>
<script src="js/booking.js"></script>
</body>
</html>
