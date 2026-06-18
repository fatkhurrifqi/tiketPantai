/* =============================================================
   TiketPantai — Micro-interactions
   1) Scroll reveal via IntersectionObserver
   2) Navbar shadow saat di-scroll
   Dimuat di semua halaman publik.
   ============================================================= */
(function () {
  'use strict';

  var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---- 1) Scroll reveal ---- */
  function initReveal() {
    var els = document.querySelectorAll('.reveal');
    if (!els.length) return;

    // Fallback: jika observer tidak didukung, tampilkan semua.
    if (prefersReduced || !('IntersectionObserver' in window)) {
      els.forEach(function (el) { el.classList.add('is-visible'); });
      return;
    }

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    els.forEach(function (el) { io.observe(el); });
  }

  /* ---- 2) Navbar shadow on scroll ---- */
  function initNav() {
    var nav = document.querySelector('.tp-nav');
    if (!nav) return;
    var onScroll = function () {
      if (window.scrollY > 8) nav.classList.add('tp-nav--scrolled');
      else nav.classList.remove('tp-nav--scrolled');
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ---- 3) Hero slideshow (autoplay + dots) ---- */
  function initSlideshow() {
    var root = document.querySelector('.tp-slides');
    if (!root) return;
    var slides = Array.prototype.slice.call(root.querySelectorAll('.tp-slide'));
    var dots = Array.prototype.slice.call(document.querySelectorAll('.tp-dot'));
    var caption = document.querySelector('.tp-caption');
    if (slides.length === 0) return;

    var idx = 0;
    var INTERVAL = 5000;
    var timer = null;

    function show(n) {
      idx = (n + slides.length) % slides.length;
      slides.forEach(function (s, i) { s.classList.toggle('is-active', i === idx); });
      dots.forEach(function (d, i) { d.classList.toggle('is-active', i === idx); });
      if (caption && dots[idx] && dots[idx].dataset.name) {
        caption.textContent = dots[idx].dataset.name;
      }
    }

    function start() {
      stop();
      if (slides.length > 1) timer = setInterval(function () { show(idx + 1); }, INTERVAL);
    }
    function stop() { if (timer) { clearInterval(timer); timer = null; } }

    // Klik dot → lompat ke slide & reset timer
    dots.forEach(function (d, i) {
      d.addEventListener('click', function () { show(i); start(); });
    });

    // Jeda saat tab tidak aktif (hemat resource)
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) stop(); else start();
    });

    show(0);
    start();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { initReveal(); initNav(); initSlideshow(); });
  } else {
    initReveal();
    initNav();
    initSlideshow();
  }
})();
