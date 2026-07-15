document.addEventListener('DOMContentLoaded', () => {

  /* ---- Footer year ---- */
  const yearEl = document.getElementById('year');
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  /* ---- Mobile nav toggle ---- */
  const navToggle = document.getElementById('navToggle');
  const mainNav = document.getElementById('mainNav');
  if (navToggle && mainNav) {
    navToggle.addEventListener('click', () => {
      const isOpen = mainNav.classList.toggle('open');
      navToggle.setAttribute('aria-expanded', String(isOpen));
    });
    mainNav.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        mainNav.classList.remove('open');
        navToggle.setAttribute('aria-expanded', 'false');
      });
    });
  }

  /* ---- Header background on scroll ---- */
  const header = document.querySelector('.site-header');
  const onScroll = () => {
    if (window.scrollY > 40) header.classList.add('scrolled');
    else header.classList.remove('scrolled');
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ---- Scroll reveal ---- */
  const revealEls = document.querySelectorAll('[data-reveal]');
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry, i) => {
        if (entry.isIntersecting) {
          const el = entry.target;
          const delay = (el.dataset.revealGroup ? i : 0) * 60;
          setTimeout(() => el.classList.add('in-view'), delay);
          io.unobserve(el);
        }
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });
    revealEls.forEach(el => io.observe(el));
  } else {
    revealEls.forEach(el => el.classList.add('in-view'));
  }

  /* ---- Cursor crosshair coordinate readout (desktop only) ---- */
  const crosshair = document.getElementById('crosshair');
  const crosshairLabel = document.getElementById('crosshairLabel');
  if (crosshair && window.matchMedia('(hover: hover)').matches) {
    document.addEventListener('mousemove', (e) => {
      crosshair.classList.add('active');
      crosshair.style.left = e.clientX + 'px';
      crosshair.style.top = e.clientY + 'px';
      const x = String(e.clientX).padStart(4, '0');
      const y = String(e.clientY).padStart(4, '0');
      crosshairLabel.textContent = `X:${x} Y:${y}`;
    });
    document.addEventListener('mouseleave', () => crosshair.classList.remove('active'));
  }

  /* ---- Contact form (AJAX to PHP handler) ---- */
  const form = document.getElementById('contactForm');
  const status = document.getElementById('formStatus');
  const submitBtn = document.getElementById('submitBtn');

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const data = new FormData(form);
      submitBtn.disabled = true;
      submitBtn.style.opacity = '0.6';
      status.textContent = 'Sending…';
      status.className = 'form-status';

      try {
        const res = await fetch('php/contact-handler.php', {
          method: 'POST',
          body: data
        });
        const result = await res.json();

        if (result.success) {
          status.textContent = 'Request sent — we\'ll reply within a business day.';
          status.className = 'form-status success';
          form.reset();
        } else {
          status.textContent = result.message || 'Something went wrong. Please try again.';
          status.className = 'form-status error';
        }
      } catch (err) {
        status.textContent = 'Could not reach the server. Please try again shortly.';
        status.className = 'form-status error';
      } finally {
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
      }
    });
  }

});
