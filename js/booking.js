// booking.js

let selectedService = null;
let selectedDate = null;
let selectedTime = null;
let calendar = null;
let currentStep = 1;

document.addEventListener('DOMContentLoaded', () => {
  loadServices();
});

// ── Services ──────────────────────────────────────
async function loadServices() {
  const data = await apiGet('php/bookings.php?action=services');
  const grid = document.getElementById('servicesGrid');
  if (!data.success) { grid.innerHTML = '<p class="text-mute">Failed to load services.</p>'; return; }

  grid.innerHTML = data.services.map(s => `
    <div class="service-card" onclick="selectService(${s.id},'${escapeHtml(s.name)}',${s.duration_minutes},${s.price},'${s.icon}')" id="svc-${s.id}">
      <div class="service-icon">${s.icon}</div>
      <div class="service-name">${s.name}</div>
      <div class="service-duration">${s.duration_minutes} min</div>
      <div class="service-price">$${parseFloat(s.price).toFixed(2)}</div>
    </div>
  `).join('');
}

function selectService(id, name, duration, price, icon) {
  selectedService = { id, name, duration, price, icon };
  document.querySelectorAll('.service-card').forEach(c => c.classList.remove('selected'));
  document.getElementById('svc-' + id).classList.add('selected');

  // Update summary
  document.getElementById('sumService').textContent = name;
  document.getElementById('sumDuration').textContent = duration + ' min';
  document.getElementById('sumPrice').textContent = '$' + parseFloat(price).toFixed(2);

  setTimeout(() => goToStep(2), 300);
}

// ── Step navigation ───────────────────────────────
function goToStep(step) {
  currentStep = step;

  document.getElementById('stepServices').style.display  = step === 1 ? '' : 'none';
  document.getElementById('stepCalendar').style.display  = step === 2 ? '' : 'none';
  document.getElementById('stepConfirm').style.display   = step === 3 ? '' : 'none';
  document.getElementById('stepSuccess').style.display   = step === 4 ? '' : 'none';

  // Update step indicators
  for (let i = 1; i <= 3; i++) {
    const el = document.getElementById('step' + i + 'ind');
    el.classList.remove('active', 'done');
    if (i < step) el.classList.add('done');
    else if (i === step) el.classList.add('active');
  }

  if (step === 2 && !calendar) initCalendar();
  if (step === 3) fillConfirmStep();
}

// ── Calendar ──────────────────────────────────────
function initCalendar() {
  const el = document.getElementById('bookingCalendar');
  calendar = new FullCalendar.Calendar(el, {
    initialView: 'dayGridMonth',
    headerToolbar: { left: 'prev', center: 'title', right: 'next' },
    height: 360,
    selectable: true,
    validRange: { start: new Date().toISOString().split('T')[0] },
    dateClick: (info) => onDateClick(info.dateStr),
    dayCellClassNames: (arg) => {
      const today = new Date().toISOString().split('T')[0];
      if (arg.dateStr === selectedDate) return ['fc-day-selected'];
      return [];
    }
  });
  calendar.render();
}

function onDateClick(dateStr) {
  // Don't allow past dates
  const today = new Date().toISOString().split('T')[0];
  if (dateStr < today) return;

  selectedDate = dateStr;
  calendar.render(); // refresh highlights

  const label = new Date(dateStr + 'T12:00:00').toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
  document.getElementById('sumDate').textContent = label;
  document.getElementById('slotsDateLabel').textContent = 'Available Times — ' + label;

  loadSlots(dateStr);
}

async function loadSlots(date) {
  const section = document.getElementById('slotsSection');
  const grid = document.getElementById('timeSlotGrid');
  section.style.display = '';
  grid.innerHTML = '<div class="text-mute">Loading...</div>';
  selectedTime = null;
  document.getElementById('sumTime').textContent = 'Not selected';

  const data = await apiGet(`php/bookings.php?action=slots&date=${date}&service_id=${selectedService?.id || 0}`);
  if (!data.success) { grid.innerHTML = '<p class="text-mute">Failed to load slots.</p>'; return; }

  const slots = data.slots;
  if (!slots.length) { grid.innerHTML = '<p class="text-mute">No slots available for this date.</p>'; return; }

  grid.innerHTML = slots.map(s => `
    <div class="time-slot ${!s.available ? 'booked' : ''}" 
         id="slot-${s.time}"
         onclick="${s.available ? `selectSlot('${s.time}','${s.display}')` : ''}">
      ${s.display}
      ${!s.available ? '<br><small style="opacity:.6">Taken</small>' : ''}
    </div>
  `).join('');
}

function selectSlot(time, display) {
  selectedTime = time;
  document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
  document.getElementById('slot-' + time).classList.add('selected');
  document.getElementById('sumTime').textContent = display;

  // Auto-advance after short delay
  setTimeout(() => {
    if (selectedService && selectedDate && selectedTime) goToStep(3);
  }, 400);
}

// ── Confirm Step ──────────────────────────────────
function fillConfirmStep() {
  if (!selectedService || !selectedDate || !selectedTime) return;
  const dateLabel = new Date(selectedDate + 'T12:00:00').toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
  const timeLabel = formatTime(selectedTime);
  document.getElementById('confirmService').textContent  = selectedService.icon + ' ' + selectedService.name;
  document.getElementById('confirmDate').textContent     = dateLabel;
  document.getElementById('confirmTime').textContent     = timeLabel;
  document.getElementById('confirmDuration').textContent = selectedService.duration + ' minutes';
}

async function confirmBooking() {
  if (!selectedService || !selectedDate || !selectedTime) {
    showToast('Please complete all selections.', 'error');
    return;
  }

  const btn = document.getElementById('confirmBtn');
  const errEl = document.getElementById('bookingError');
  errEl.classList.remove('show');
  btn.innerHTML = '<span class="loading"></span> Processing...';
  btn.disabled = true;

  const data = await apiPost('php/bookings.php', {
    action:     'create',
    service_id: selectedService.id,
    date:       selectedDate,
    time:       selectedTime,
    notes:      document.getElementById('bookingNotes').value
  });

  if (data.success) {
    const dateLabel = new Date(selectedDate + 'T12:00:00').toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
    document.getElementById('successRef').textContent      = data.booking_ref;
    document.getElementById('successService').textContent  = selectedService.icon + ' ' + selectedService.name;
    document.getElementById('successDateTime').textContent = dateLabel + ' at ' + formatTime(selectedTime);
    goToStep(4);
  } else {
    errEl.textContent = data.error || 'Booking failed. Please try again.';
    errEl.classList.add('show');
    btn.innerHTML = '<span>Confirm Booking</span>';
    btn.disabled = false;
  }
}

// ── Helpers ───────────────────────────────────────
function escapeHtml(str) {
  return str.replace(/'/g, "\\'");
}

function formatTime(timeStr) {
  if (!timeStr) return '—';
  const [h, m] = timeStr.split(':');
  const hour = parseInt(h);
  return `${hour % 12 || 12}:${m} ${hour < 12 ? 'AM' : 'PM'}`;
}

// Highlight selected date cell
const origStyle = document.createElement('style');
origStyle.textContent = `.fc-day-selected .fc-daygrid-day-frame { background: rgba(201,168,76,0.15) !important; border-radius: 6px; }`;
document.head.appendChild(origStyle);
