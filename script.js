document.addEventListener('DOMContentLoaded', () => {
    // 0. ScrollReveal - Animations d'entrée
    // 0. ScrollReveal - Animations d'entrée
    // 0. ScrollReveal - Animations d'entrée
    if (typeof ScrollReveal !== 'undefined') {
        const sr = ScrollReveal({
            duration: 900,
            distance: '60px',
            easing: 'cubic-bezier(0.165, 0.84, 0.44, 1)',
            reset: true,
        });

        // ---------- HERO (index.php) ----------
        sr.reveal('.hero-text', { origin: 'left', distance: '80px', duration: 1000, delay: 100 });
        sr.reveal('.hero-brackets', { origin: 'right', distance: '80px', duration: 1000, delay: 250 });

        // ---------- A PROPOS (bloc "about", partagé index.php + apropos.php) ----------
        sr.reveal('.about-image', { origin: 'left', distance: '70px', duration: 1000, delay: 100 });
        sr.reveal('.about-content', { origin: 'right', distance: '70px', duration: 1000, delay: 250 });

        // ---------- SERVICES (index.php) ----------
        sr.reveal('#services .section-title', { origin: 'top', distance: '50px', duration: 800 });
        sr.reveal('#services .service-card', { origin: 'bottom', distance: '50px', duration: 700, interval: 150 });

        // ---------- PORTFOLIO (index.php + portfolio.php) ----------
        sr.reveal('#portfolio .section-title', { origin: 'top', distance: '50px', duration: 800 });
        sr.reveal('.portfolio-filters', { origin: 'top', distance: '30px', duration: 700, delay: 150 });
        sr.reveal('.portfolio-item', { origin: 'bottom', distance: '50px', duration: 700, interval: 120 });

        // ---------- TEMOIGNAGES (index.php) ----------
        sr.reveal('.temoignages-pretitle', { origin: 'top', distance: '30px', duration: 700 });
        sr.reveal('.temoignages-title', { origin: 'top', distance: '40px', duration: 800, delay: 100 });
        sr.reveal('.temoignage-card', { origin: 'bottom', distance: '40px', duration: 700, scale: 0.94, interval: 130 });
        sr.reveal('.temoignages-stats', { origin: 'bottom', distance: '40px', duration: 800, delay: 200 });

        // ---------- FOOTER (toutes les pages) ----------
        sr.reveal('.footer-logo-box', { origin: 'bottom', distance: '40px', duration: 700 });
        sr.reveal('.footer-col', { origin: 'bottom', distance: '40px', duration: 700, interval: 150 });
        sr.reveal('.footer-bottom', { origin: 'bottom', distance: '30px', duration: 700, delay: 300 });

        // ==================================================================
        // ---------- A PROPOS (apropos.php) : sections supplémentaires ----------
        // ==================================================================

        // Histoire
        sr.reveal('.history .section-title', { origin: 'top', distance: '40px', duration: 800 });
        sr.reveal('.history p', { origin: 'bottom', distance: '40px', duration: 800, delay: 150 });

        // Vision & Mission
        sr.reveal('.vm .section-title', { origin: 'top', distance: '40px', duration: 800 });
        sr.reveal('.vm .service-detail-content', { origin: 'bottom', distance: '40px', duration: 700, interval: 150 });

        // Valeurs
        sr.reveal('.values .section-title', { origin: 'top', distance: '40px', duration: 800 });
        sr.reveal('.values .about-list li', { origin: 'bottom', distance: '30px', duration: 650, interval: 100 });

        // Equipe
        sr.reveal('.team .section-title', { origin: 'top', distance: '40px', duration: 800 });
        sr.reveal('.team .service-card', { origin: 'bottom', distance: '50px', duration: 700, interval: 150 });

        // Matériel professionnel
        sr.reveal('.equipment .section-title', { origin: 'top', distance: '40px', duration: 800 });
        sr.reveal('.equipment .service-detail-image-box', { origin: 'left', distance: '70px', duration: 900, delay: 100 });
        sr.reveal('.equipment .service-detail-content', { origin: 'right', distance: '70px', duration: 900, delay: 250 });

        // Certifications
        sr.reveal('.certifications .section-title', { origin: 'top', distance: '40px', duration: 800 });
        sr.reveal('.certifications .service-card', { origin: 'bottom', distance: '50px', duration: 700, interval: 150 });

        // ==================================================================
        // ---------- SERVICES DÉTAILLÉS (service.php) ----------
        // ==================================================================

        sr.reveal('#services-detail .section-title', { origin: 'top', distance: '50px', duration: 800 });

        // Lignes impaires : image à gauche, texte à droite
        sr.reveal('.service-detail-row:nth-child(odd) .service-detail-image-box', {
            origin: 'left', distance: '70px', duration: 900, delay: 100,
        });
        sr.reveal('.service-detail-row:nth-child(odd) .service-detail-content', {
            origin: 'right', distance: '70px', duration: 900, delay: 200,
        });

        // Lignes paires : image à droite, texte à gauche (cohérent avec ton layout alterné en CSS)
        sr.reveal('.service-detail-row:nth-child(even) .service-detail-image-box', {
            origin: 'right', distance: '70px', duration: 900, delay: 100,
        });
        sr.reveal('.service-detail-row:nth-child(even) .service-detail-content', {
            origin: 'left', distance: '70px', duration: 900, delay: 200,
        });

        // Sous-cartes de chaque prestation : affichage en cascade
        sr.reveal('.sub-service-card', { origin: 'bottom', distance: '40px', duration: 650, interval: 100 });

    } else {
        console.warn('ScrollReveal NON chargé ❌');
    }

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

    /*const devis = document.getElementById("btn-open-devis");
    
    devis.addEventListener('click',(e)=>{

        alert('click');

    });*/
    // 8. Modal Demande de Devis
    const devisModal = document.getElementById('devis-modal');
    const btnOpenDevis = document.getElementById('btn-open-devis');
    const btnOpenDevisFloat = document.getElementById('btn-open-devis-float');
    const devisModalClose = document.getElementById('devis-modal-close');
    const devisModalOverlay = document.getElementById('devis-modal-overlay');
    const devisAnnuler = document.getElementById('devis-annuler');
    const devisForm = document.getElementById('devis-form');

    if (devisModal && (btnOpenDevis || btnOpenDevisFloat)) {
        const openDevisModal = (e) => {
            e.preventDefault();
            devisModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        };

        const closeDevisModal = () => {
            devisModal.classList.remove('active');
            document.body.style.overflow = '';
        };

        if (btnOpenDevis) {
            btnOpenDevis.addEventListener('click', openDevisModal);
        }
        if (btnOpenDevisFloat) {
            btnOpenDevisFloat.addEventListener('click', openDevisModal);
        }
        devisModalClose.addEventListener('click', closeDevisModal);
        devisModalOverlay.addEventListener('click', closeDevisModal);
        devisAnnuler.addEventListener('click', closeDevisModal);

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && devisModal.classList.contains('active')) {
                closeDevisModal();
            }
        });

        // Optionnel : soumission en AJAX au lieu d'un rechargement de page
        // devisForm.addEventListener('submit', (e) => {
        //     e.preventDefault();
        //     const formData = new FormData(devisForm);
        //     fetch('traitement_devis.php', { method: 'POST', body: formData })
        //         .then(res => res.json())
        //         .then(data => { /* afficher message succès */ });
        // });

        const prestationSelect = document.getElementById('devis-prestation');
        const categoriesList = document.getElementById('devis-categories-list');

        if (prestationSelect && categoriesList) {
            prestationSelect.addEventListener('change', () => {
                const idPrestation = prestationSelect.value;

                categoriesList.innerHTML = '';

                if (!idPrestation) {
                    return; // rien tant qu'aucune prestation n'est choisie
                }

                categoriesList.innerHTML = '<p class="devis-categories-loading">Chargement...</p>';

                fetch(`categorie.php?id_prestation=${encodeURIComponent(idPrestation)}`)
                    .then(res => res.json())
                    .then(categories => {
                        categoriesList.innerHTML = '';

                        if (!Array.isArray(categories) || categories.length === 0) {
                            categoriesList.innerHTML = '<p class="devis-categories-empty">Aucune catégorie disponible</p>';
                            return;
                        }

                        categories.forEach(cat => {
                            const item = document.createElement('label');
                            item.className = 'devis-category-checkbox';
                            item.innerHTML = `
                        <input type="checkbox" name="id_categorie[]" value="${cat.ID_CATEGORIE}">
                        <span>${cat.LIB_CATEGORIE}</span>
                    `;
                            categoriesList.appendChild(item);
                        });
                    })
                    .catch(() => {
                        categoriesList.innerHTML = '<p class="devis-categories-error">Erreur de chargement</p>';
                    });
            });
        }
    }


});