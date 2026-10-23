// BrewCart — tiny front-end helpers. No libraries, no build step.

// Run when the page is ready.
document.addEventListener('DOMContentLoaded', function () {

  // --- mobile navigation toggle ---
  var toggle = document.querySelector('[data-nav-toggle]');
  var nav = document.querySelector('[data-nav]');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      nav.classList.toggle('open');
    });
  }

  // --- dismiss flash messages ---
  document.querySelectorAll('[data-flash-close]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var box = btn.closest('.flash');
      if (box) {
        box.remove();
      }
    });
  });

  // --- "are you sure?" on delete forms (admin) ---
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        e.preventDefault();
      }
    });
  });

  // --- checkout page: show address + matching payment methods per order type ---
  var typeRadios = document.querySelectorAll('input[name="order_type"]');
  var addressWrap = document.querySelector('[data-address-wrap]');
  var payOptions = document.querySelectorAll('[data-payment-option]');
  if (typeRadios.length && (addressWrap || payOptions.length)) {
    var currentType = function () {
      var checked = document.querySelector('input[name="order_type"]:checked');
      return checked ? checked.value : 'pickup';
    };
    var refresh = function () {
      var type = currentType();
      if (addressWrap) {
        addressWrap.style.display = type === 'delivery' ? '' : 'none';
      }
      var firstVisible = null;
      payOptions.forEach(function (opt) {
        var types = (opt.getAttribute('data-types') || '').split(' ');
        var show = types.indexOf(type) !== -1;
        opt.style.display = show ? '' : 'none';
        if (show && !firstVisible) {
          firstVisible = opt;
        }
      });
      // If the selected payment method is hidden for this order type,
      // select the first visible one instead.
      var selected = document.querySelector('[data-payment-option] input:checked');
      if (selected && selected.closest('[data-payment-option]').style.display === 'none' && firstVisible) {
        firstVisible.querySelector('input').checked = true;
      }
    };
    typeRadios.forEach(function (r) {
      r.addEventListener('change', refresh);
    });
    refresh();
  }
});
