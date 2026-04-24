// JavaScript para funcionalidades básicas
document.addEventListener('DOMContentLoaded', function() {


    // PANEL DE USUARIO DESPLEGABLE
    if (!window.USER_PANEL_COMPONENT_LOADED) {
        const userPanel = document.getElementById('userPanel');
        const userToggle = document.getElementById('userToggle');
        
        if (userToggle && userPanel) {
            userToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                userPanel.classList.toggle('active');
            });
            
            document.addEventListener('click', function() {
                userPanel.classList.remove('active');
            });
            
            const userDropdown = userPanel.querySelector('.user-dropdown');
            if (userDropdown) {
                userDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        }
        // Evitar inicialización duplicada en futuras cargas del script
        window.USER_PANEL_COMPONENT_LOADED = true;
    }

    // Menú móvil mejorado
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            navMenu.classList.toggle('active');
        });
        
        const navLinks = navMenu.querySelectorAll('a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                navMenu.classList.remove('active');
            });
        });
        
        document.addEventListener('click', function(event) {
            const isClickInsideNav = navMenu.contains(event.target);
            const isClickOnToggle = navToggle.contains(event.target);
            
            if (!isClickInsideNav && !isClickOnToggle && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
            }
        });
        
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                navMenu.classList.remove('active');
            }
        });
    }
    
    // ============================================
    // SLIDER HERO - 10 segundos - CORREGIDO
    // ============================================
    const heroSlider = document.getElementById('heroSlider');
    const heroSlides = document.querySelectorAll('.hero-slide');
    const heroDots = document.querySelectorAll('.hero-slider-dot');
    
    if (heroSlider && heroSlides.length > 0) {
        let currentHeroSlide = 0;
        let heroInterval;
        let isHeroPaused = false;
        
        function goToHeroSlide(index) {
            if (index < 0) index = heroSlides.length - 1;
            if (index >= heroSlides.length) index = 0;
            
            heroSlider.style.transform = `translateX(-${index * 100}%)`;
            heroDots.forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });
            currentHeroSlide = index;
        }
        
        function startHeroAutoPlay() {
            if (heroInterval) clearInterval(heroInterval);
            if (!isHeroPaused) {
                heroInterval = setInterval(() => {
                    goToHeroSlide(currentHeroSlide + 1);
                }, 5000); // 10 segundos
            }
        }
        
        function pauseHeroAutoPlay() {
            isHeroPaused = true;
            if (heroInterval) {
                clearInterval(heroInterval);
                heroInterval = null;
            }
        }
        
        function resumeHeroAutoPlay() {
            isHeroPaused = false;
            startHeroAutoPlay();
        }
        
        // Inicializar
        goToHeroSlide(0);
        startHeroAutoPlay();
        
        // Event listeners para dots
        heroDots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                pauseHeroAutoPlay();
                goToHeroSlide(index);
                setTimeout(resumeHeroAutoPlay, 10000);
            });
        });
        
        // Pausar al hacer hover
        heroSlider.addEventListener('mouseenter', pauseHeroAutoPlay);
        heroSlider.addEventListener('mouseleave', resumeHeroAutoPlay);
        
        // Soporte para touch en móvil
        let touchStartX = 0;
        heroSlider.addEventListener('touchstart', (e) => {
            touchStartX = e.touches[0].clientX;
            pauseHeroAutoPlay();
        });
        
        heroSlider.addEventListener('touchend', (e) => {
            const touchEndX = e.changedTouches[0].clientX;
            const diff = touchStartX - touchEndX;
            
            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    goToHeroSlide(currentHeroSlide + 1);
                } else {
                    goToHeroSlide(currentHeroSlide - 1);
                }
            }
            setTimeout(resumeHeroAutoPlay, 10000);
        });
    }
    
    // ============================================
    // SLIDER DE PROMOCIONES - UN SOLO SLIDER - CORREGIDO
    // ============================================
    function setupPromoSlider() {
        const mainPromoSlider = document.getElementById('main-promo-slider');
        if (!mainPromoSlider) return;
        
        const slides = mainPromoSlider.querySelectorAll('.promo-slide');
        if (slides.length === 0) return;
        
        let currentSlide = 0;
        let promoInterval;
        let isPromoPaused = false;
        
        // Función para ir a un slide específico
        function goToSlide(index) {
            if (index < 0) index = slides.length - 1;
            if (index >= slides.length) index = 0;
            
            slides.forEach(slide => slide.classList.remove('active'));
            slides[index].classList.add('active');
            currentSlide = index;
        }
        
        function startPromoAutoPlay() {
            if (promoInterval) clearInterval(promoInterval);
            if (!isPromoPaused) {
                promoInterval = setInterval(() => {
                    goToSlide(currentSlide + 1);
                }, 12000); // 12 segundos
            }
        }
        
        function pausePromoAutoPlay() {
            isPromoPaused = true;
            if (promoInterval) {
                clearInterval(promoInterval);
                promoInterval = null;
            }
        }
        
        function resumePromoAutoPlay() {
            isPromoPaused = false;
            startPromoAutoPlay();
        }
        
        // Botones de navegación
        const prevBtn = mainPromoSlider.querySelector('.promo-slider-nav.prev');
        const nextBtn = mainPromoSlider.querySelector('.promo-slider-nav.next');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                pausePromoAutoPlay();
                goToSlide(currentSlide - 1);
                setTimeout(resumePromoAutoPlay, 12000);
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                pausePromoAutoPlay();
                goToSlide(currentSlide + 1);
                setTimeout(resumePromoAutoPlay, 12000);
            });
        }
        
        // Inicializar
        goToSlide(0);
        startPromoAutoPlay();
        
        // Pausar al hacer hover
        mainPromoSlider.addEventListener('mouseenter', pausePromoAutoPlay);
        mainPromoSlider.addEventListener('mouseleave', resumePromoAutoPlay);
        
        slides.forEach(slide => {
            slide.addEventListener('mouseenter', pausePromoAutoPlay);
            slide.addEventListener('mouseleave', resumePromoAutoPlay);
        });
        
        // Soporte para touch en móvil
        let touchStartX = 0;
        mainPromoSlider.addEventListener('touchstart', (e) => {
            touchStartX = e.touches[0].clientX;
            pausePromoAutoPlay();
        });
        
        mainPromoSlider.addEventListener('touchend', (e) => {
            const touchEndX = e.changedTouches[0].clientX;
            const diff = touchStartX - touchEndX;
            
            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    goToSlide(currentSlide + 1);
                } else {
                    goToSlide(currentSlide - 1);
                }
            }
            setTimeout(resumePromoAutoPlay, 12000);
        });
    }
    
    // ============================================
    // SLIDERS DE PRODUCTOS Y VEHÍCULOS - CORREGIDO
    // ============================================
    function setupProductSliders() {
        const sliders = [
            {
                track: document.getElementById('track-productos'),
                prev: document.getElementById('prev-productos'),
                next: document.getElementById('next-productos'),
                container: document.querySelectorAll('.slider-container-custom')[0]
            },
            {
                track: document.getElementById('track-vehiculos'),
                prev: document.getElementById('prev-vehiculos'),
                next: document.getElementById('next-vehiculos'),
                container: document.querySelectorAll('.slider-container-custom')[1]
            }
        ];
        
        const itemWidth = 265 + 25; // Ancho de producto + gap
        let scrollTimeout;
        
        sliders.forEach((slider, index) => {
            if (!slider.track || !slider.prev || !slider.next) return;
            
            // Mostrar/ocultar controles al hacer hover
            if (slider.container) {
                slider.container.addEventListener('mouseenter', () => {
                    slider.prev.style.opacity = '1';
                    slider.prev.style.visibility = 'visible';
                    slider.next.style.opacity = '1';
                    slider.next.style.visibility = 'visible';
                });
                
                slider.container.addEventListener('mouseleave', () => {
                    // No ocultar si estamos en móvil
                    if (window.innerWidth > 768) {
                        slider.prev.style.opacity = '0';
                        slider.prev.style.visibility = 'hidden';
                        slider.next.style.opacity = '0';
                        slider.next.style.visibility = 'hidden';
                    }
                });
            }
            
            // Event listeners para los botones
            slider.next.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const maxScroll = slider.track.scrollWidth - slider.track.clientWidth;
                const newScroll = Math.min(slider.track.scrollLeft + (itemWidth * 2), maxScroll);
                
                slider.track.scrollTo({
                    left: newScroll,
                    behavior: 'smooth'
                });
            });
            
            slider.prev.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const newScroll = Math.max(slider.track.scrollLeft - (itemWidth * 2), 0);
                
                slider.track.scrollTo({
                    left: newScroll,
                    behavior: 'smooth'
                });
            });
            
            // Soporte para scroll con rueda del mouse
            slider.track.addEventListener('wheel', (e) => {
                e.preventDefault();
                
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    slider.track.scrollBy({
                        left: e.deltaY > 0 ? itemWidth : -itemWidth,
                        behavior: 'smooth'
                    });
                }, 50);
            }, { passive: false });
            
            // En móvil, aseguramos que los controles sean visibles
            if (window.innerWidth <= 768) {
                slider.prev.style.opacity = '1';
                slider.prev.style.visibility = 'visible';
                slider.next.style.opacity = '1';
                slider.next.style.visibility = 'visible';
            }
        });
    }
    
    // ============================================
    // AJUSTES PARA MÓVIL
    // ============================================
    function setupMobileLayout() {
        const mainPromoSlider = document.getElementById('main-promo-slider');
        
        if (window.innerWidth <= 768) {
            // En móvil, ajustar altura del slider de promociones
            if (mainPromoSlider) {
                mainPromoSlider.style.height = '300px';
            }
            
            // Hacer visibles los controles de navegación
            const promoNavBtns = document.querySelectorAll('.promo-slider-nav');
            promoNavBtns.forEach(btn => {
                btn.style.opacity = '1';
                btn.style.visibility = 'visible';
            });
        } else {
            // Restaurar en desktop
            if (mainPromoSlider) {
                mainPromoSlider.style.height = '';
            }
        }
    }
    
    // ============================================
    // INICIALIZAR TODO
    // ============================================
    
    // Inicializar sliders
    setupPromoSlider();
    setupProductSliders();
    setupMobileLayout();
    
    // Reconfigurar cuando cambie el tamaño de la ventana
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            setupMobileLayout();
            setupProductSliders(); // Reconfigurar sliders de productos
        }, 250);
    });
    
    // Barra de búsqueda - funcionalidad con Enter
    const searchBar = document.querySelector('.search-bar');
    const searchIcon = document.querySelector('.search-icon');
    
    if (searchBar && searchIcon) {
        // Hacer focus al hacer clic en el icono
        searchIcon.addEventListener('click', () => searchBar.focus());
        
        // Cerrar menú móvil si está abierto
        searchBar.addEventListener('focus', () => {
            if (navMenu && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
            }
        });
        
        // Búsqueda al presionar Enter - usar keydown para mejor compatibilidad
        searchBar.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const query = searchBar.value.trim();
                console.log('Search triggered:', query);
                if (query.length >= 2) {
                    const baseUrl = document.querySelector('[data-base-url]')?.getAttribute('data-base-url') || 
                                   (typeof BASE_URL !== 'undefined' ? BASE_URL : '/inversiones-rojas');
                    const searchUrl = baseUrl + '/app/views/layouts/search_results.php?q=' + encodeURIComponent(query);
                    console.log('Redirecting to:', searchUrl);
                    window.location.href = searchUrl;
                } else {
                    console.log('Query too short:', query.length);
                }
            }
        });
    }
});

// ============================================
// FUNCIONES GLOBALES DEL PANEL DE USUARIO
// ============================================
function openSettings() {
    alert('Configuración - Esta funcionalidad estará disponible pronto');
    const userPanel = document.getElementById('userPanel');
    if (userPanel) userPanel.classList.remove('active');
}

function openProfile() {
    alert('Perfil de usuario - Esta funcionalidad estará disponible pronto');
    const userPanel = document.getElementById('userPanel');
    if (userPanel) userPanel.classList.remove('active');
}

function openOrders() {
    alert('Historial de pedidos - Esta funcionalidad estará disponible pronto');
    const userPanel = document.getElementById('userPanel');
    if (userPanel) userPanel.classList.remove('active');
}