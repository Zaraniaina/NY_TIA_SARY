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
            const opened = navList.classList.toggle('active');
            mobileMenu.classList.toggle('active', opened);
            mobileMenu.setAttribute('aria-expanded', opened ? 'true' : 'false');
        });

        // Close menu when clicking on a link
        const navLinks = navList.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                navList.classList.remove('active');
                mobileMenu.classList.remove('active');
                mobileMenu.setAttribute('aria-expanded', 'false');
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

    // 7. Nouveau Slider Témoignages Simple
    const slides = document.querySelectorAll('.temoignage-slide');
    const prevBtn = document.getElementById('temo-prev');
    const nextBtn = document.getElementById('temo-next');
    const dots = document.querySelectorAll('.temo-dot');

    if (slides.length > 0 && prevBtn && nextBtn) {
        let currentSlide = 0;
        let slideInterval = null;

        const showSlide = (index) => {
            // Remove active classes
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            // Boundary checks
            if (index >= slides.length) currentSlide = 0;
            else if (index < 0) currentSlide = slides.length - 1;
            else currentSlide = index;

            // Add active class
            slides[currentSlide].classList.add('active');
            if (dots.length > 0 && dots[currentSlide]) {
                dots[currentSlide].classList.add('active');
            }
        };

        const nextSlide = () => { showSlide(currentSlide + 1); resetInterval(); };
        const prevSlide = () => { showSlide(currentSlide - 1); resetInterval(); };

        // Events
        nextBtn.addEventListener('click', nextSlide);
        prevBtn.addEventListener('click', prevSlide);
        
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index);
                resetInterval();
            });
        });

        // Autoplay
        const startInterval = () => { slideInterval = setInterval(nextSlide, 5000); };
        const resetInterval = () => { clearInterval(slideInterval); startInterval(); };

        startInterval();

        // Pause on hover
        const trackContainer = document.getElementById('temo-track');
        if (trackContainer) {
            trackContainer.addEventListener('mouseenter', () => clearInterval(slideInterval));
            trackContainer.addEventListener('mouseleave', startInterval);
            
            // Swipe support
            let touchStartX = 0;
            trackContainer.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
            trackContainer.addEventListener('touchend', e => {
                const diff = touchStartX - e.changedTouches[0].clientX;
                if (Math.abs(diff) > 50) { diff > 0 ? nextSlide() : prevSlide(); }
            });
        }
    }
});