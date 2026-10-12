(function () {
  var form = document.getElementById('reserve-form');
  if (!form) return;

  var start = document.getElementById('start_local');
  var amount = document.getElementById('amount');
  var unit = document.getElementById('unit');
  var end = document.getElementById('end_local');
  var preview = document.getElementById('preview');

  function mode() {
    var checked = form.querySelector('input[name="mode"]:checked');
    return checked ? checked.value : 'duration';
  }

  function parseWall(value) {
    var m = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/.exec(value || '');
    if (!m) return null;
    return new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]), Number(m[4]), Number(m[5]));
  }

  function formatWall(date) {
    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    var h = date.getHours();
    var am = h >= 12 ? 'PM' : 'AM';
    var h12 = h % 12;
    if (h12 === 0) h12 = 12;
    var min = String(date.getMinutes()).padStart(2, '0');
    return months[date.getMonth()] + ' ' + date.getDate() + ', ' + date.getFullYear() + ' ' + h12 + ':' + min + ' ' + am;
  }

  function addDuration(startDate, n, kind) {
    var d = new Date(startDate.getTime());
    if (kind === 'hours') d.setHours(d.getHours() + n);
    else if (kind === 'weeks') d.setDate(d.getDate() + n * 7);
    else d.setDate(d.getDate() + n);
    return d;
  }

  function refresh() {
    var startDate = parseWall(start.value);
    if (!startDate) {
      preview.textContent = 'Choose a start time in Eastern Time.';
      return;
    }
    var endDate;
    if (mode() === 'end') {
      endDate = parseWall(end.value);
      if (!endDate) {
        preview.textContent = 'Choose an end time, or switch to duration.';
        return;
      }
    } else {
      endDate = addDuration(startDate, parseInt(amount.value || '0', 10), unit.value);
    }
    var ms = endDate.getTime() - startDate.getTime();
    var max = 21 * 24 * 60 * 60 * 1000;
    if (ms <= 0) {
      preview.textContent = 'End must be after start.';
      return;
    }
    var extra = ms > max ? ' This is over the 3-week maximum.' : '';
    preview.textContent = 'Ends ' + formatWall(endDate) + ' ET.' + extra;
  }

  form.addEventListener('input', refresh);
  form.addEventListener('change', refresh);
  refresh();
})();
