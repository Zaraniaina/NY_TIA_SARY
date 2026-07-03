document.addEventListener('DOMContentLoaded', () => {
    // 1. Video Play/Pause Control
    const bannerVideo = document.getElementById('banner-video');
    const playPauseBtn = document.getElementById('play-pause-btn');

    if (bannerVideo && playPauseBtn) {
        playPauseBtn.addEventListener('click', () => {
            if (bannerVideo.paused) {
                bannerVideo.play();
                playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
                playPauseBtn.setAttribute('aria-label', 'Mettre en pause la vidéo');
            } else {
                bannerVideo.pause();
                playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                playPauseBtn.setAttribute('aria-label', 'Lancer la vidéo');
            }
        });
    }

    // 2. Scroll Header Effect
    const header = document.getElementById('header');
    if (header) {
        const handleScroll = () => {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        };
        // Run once on load and on scroll
        handleScroll();
        window.addEventListener('scroll', handleScroll);
    }

    // 3. Smooth scroll for banner scroll button
    const scrollBtn = document.getElementById('scroll-to-hero');
    if (scrollBtn) {
        scrollBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = scrollBtn.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    }

    // 4. Mobile Menu Toggle
    const mobileMenu = document.getElementById('mobile-menu');
    const navList = document.getElementById('nav-list');

    if (mobileMenu && navList) {
        mobileMenu.addEventListener('click', () => {
            navList.classList.toggle('active');
            // Toggle hamburger icon animation or state
            const icon = mobileMenu.querySelector('i');
            if (icon) {
                if (navList.classList.contains('active')) {
                    icon.className = 'fas fa-times';
                } else {
                    icon.className = 'fas fa-bars';
                }
            }
        });

        // Close menu when clicking on a link
        const navLinks = navList.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                navList.classList.remove('active');
                const icon = mobileMenu.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-bars';
                }
            });
        });
    }

    // 5. Lightbox Functionality (Safe check)
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxClose = document.getElementById('lightbox-close');
    const portfolioItems = document.querySelectorAll('.portfolio-item');

    if (lightbox && lightboxImg && lightboxClose) {
        portfolioItems.forEach(item => {
            item.addEventListener('click', () => {
                const img = item.querySelector('img');
                if (img) {
                    lightboxImg.src = img.src;
                    lightboxImg.alt = img.alt;
                    lightbox.classList.add('active');
                    document.body.style.overflow = 'hidden'; // Stop page scrolling
                }
            });
        });

        const closeLightbox = () => {
            lightbox.classList.remove('active');
            lightboxImg.src = '';
            document.body.style.overflow = ''; // Re-enable page scrolling
        };

        lightboxClose.addEventListener('click', closeLightbox);
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) {
                closeLightbox();
            }
        });

        // Close lightbox on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && lightbox.classList.contains('active')) {
                closeLightbox();
            }
        });
    }

    // 6. Portfolio Filtering (For portfolio.php if loaded)
    const filterButtons = document.querySelectorAll('.filter-btn');
    const gridItems = document.querySelectorAll('.portfolio-item');

    if (filterButtons.length > 0 && gridItems.length > 0) {
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Active class toggle
                filterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                const filterValue = button.getAttribute('data-filter');

                gridItems.forEach(item => {
                    const itemCategory = item.getAttribute('data-category');
                    if (filterValue === 'all' || itemCategory === filterValue) {
                        item.style.display = 'block';
                        // Add fade-in micro-animation
                        item.style.opacity = '0';
                        setTimeout(() => {
                            item.style.transition = 'opacity 0.4s ease';
                            item.style.opacity = '1';
                        }, 50);
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    }
    // 7. Carrousel Témoignages
    const track = document.getElementById('temoignages-track');
    const prevBtn = document.getElementById('temoignage-prev');
    const nextBtn = document.getElementById('temoignage-next');
    const dots = document.querySelectorAll('.temoignage-dot');

    if (track && prevBtn && nextBtn && dots.length) {
        const cards = track.querySelectorAll('.temoignage-card');
        const totalCards = cards.length;
        let currentIndex = 0;
        let autoPlayTimer = null;

        // Determine how many cards are visible based on viewport width
        const getVisibleCount = () => {
            if (window.innerWidth <= 680) return 1;
            if (window.innerWidth <= 1024) return 2;
            return 3;
        };

        const maxIndex = () => totalCards - getVisibleCount();

        const goTo = (index) => {
            const max = maxIndex();
            currentIndex = Math.max(0, Math.min(index, max));

            // Card width = track width / visible count + gap contribution
            const wrapWidth = track.parentElement.offsetWidth;
            const visible = getVisibleCount();
            const gap = 30;
            const cardWidth = (wrapWidth - gap * (visible - 1)) / visible;
            const offset = currentIndex * (cardWidth + gap);

            track.style.transform = `translateX(-${offset}px)`;

            // Update dots
            dots.forEach((d, i) => d.classList.toggle('active', i === currentIndex));
        };

        const next = () => goTo(currentIndex >= maxIndex() ? 0 : currentIndex + 1);
        const prev = () => goTo(currentIndex <= 0 ? maxIndex() : currentIndex - 1);

        nextBtn.addEventListener('click', () => { next(); resetAutoPlay(); });
        prevBtn.addEventListener('click', () => { prev(); resetAutoPlay(); });

        dots.forEach(dot => {
            dot.addEventListener('click', () => {
                goTo(parseInt(dot.dataset.index, 10));
                resetAutoPlay();
            });
        });

        // Auto-play every 5 seconds
        const startAutoPlay = () => { autoPlayTimer = setInterval(next, 5000); };
        const resetAutoPlay = () => { clearInterval(autoPlayTimer); startAutoPlay(); };

        startAutoPlay();

        // Pause on hover
        track.addEventListener('mouseenter', () => clearInterval(autoPlayTimer));
        track.addEventListener('mouseleave', startAutoPlay);

        // Recalculate on resize
        window.addEventListener('resize', () => goTo(currentIndex));

        // Touch / swipe support
        let touchStartX = 0;
        track.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
        track.addEventListener('touchend', e => {
            const diff = touchStartX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 50) { diff > 0 ? next() : prev(); resetAutoPlay(); }
        });

        // Initial render
        goTo(0);
    }
});
