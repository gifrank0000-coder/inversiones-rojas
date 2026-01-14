// JavaScript para funcionalidades básicas
document.addEventListener('DOMContentLoaded', function() {
    // CONEXIÓN DE BOTONES DE LOGIN Y REGISTRO
    const base = window.APP_BASE || '';
    const loginButtons = document.querySelectorAll('.login-btn');
    loginButtons.forEach(button => {
        button.addEventListener('click', function() {
            const url = base ? (base + '/app/views/auth/Login.php') : '/app/views/auth/Login.php';
            window.location.href = url;
        });
    });
    
    const registerButtons = document.querySelectorAll('.register-btn');
    registerButtons.forEach(button => {
        button.addEventListener('click', function() {
            const url = base ? (base + '/app/views/auth/register.php') : '/app/views/auth/register.php';
            window.location.href = url;
        });
    });

    // PANEL DE USUARIO DESPLEGABLE: si ya cargó el componente específico, no inicializamos de nuevo
    if (!window.USER_PANEL_COMPONENT_LOADED) {
        const userPanel = document.getElementById('userPanel');
        const userToggle = document.getElementById('userToggle');
        
        if (userToggle && userPanel) {
            // Toggle del dropdown
            userToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                userPanel.classList.toggle('active');
            });
            
            // Cerrar dropdown al hacer clic fuera
            document.addEventListener('click', function() {
                userPanel.classList.remove('active');
            });
            
            // Prevenir que el dropdown se cierre al hacer clic dentro
            const userDropdown = userPanel.querySelector('.user-dropdown');
            if (userDropdown) {
                userDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
            
            // Conectar botones del dropdown
            const profileBtn = userPanel.querySelector('.dropdown-item[onclick*="openProfile"]');
            const ordersBtn = userPanel.querySelector('.dropdown-item[onclick*="openOrders"]');
            const settingsBtn = userPanel.querySelector('.dropdown-item[onclick*="openSettings"]');
            
            if (profileBtn) {
                profileBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    openProfile();
                });
            }
            
            if (ordersBtn) {
                ordersBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    openOrders();
                });
            }
            
            if (settingsBtn) {
                settingsBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    openSettings();
                });
            }
        }
    }

    // Menú móvil mejorado
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            navMenu.classList.toggle('active');
        });
        
        // Cerrar menú al hacer clic en un enlace
        const navLinks = navMenu.querySelectorAll('a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                navMenu.classList.remove('active');
            });
        });
        
        // Cerrar menú al hacer clic fuera de él
        document.addEventListener('click', function(event) {
            const isClickInsideNav = navMenu.contains(event.target);
            const isClickOnToggle = navToggle.contains(event.target);
            
            if (!isClickInsideNav && !isClickOnToggle && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
            }
        });
        
        // Cerrar menú al redimensionar la ventana (si se cambia a desktop)
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                navMenu.classList.remove('active');
            }
        });
    }
    
    // Slider automático HERO
    const heroSlider = document.getElementById('heroSlider');
    const heroSlides = document.querySelectorAll('.hero-slide');
    const heroDots = document.querySelectorAll('.hero-slider-dot');
    
    if (heroSlider && heroSlides.length > 0) {
        let currentHeroSlide = 0;
        let heroInterval;
        
        function goToHeroSlide(index) {
            heroSlider.style.transform = `translateX(-${index * 100}%)`;
            
            // Actualizar dots activos
            heroDots.forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });
            
            currentHeroSlide = index;
        }
        
        // Iniciar slider hero
        goToHeroSlide(0);
        
        // Cambiar slide cada 5 segundos
        heroInterval = setInterval(() => {
            let nextSlide = (currentHeroSlide + 1) % heroSlides.length;
            goToHeroSlide(nextSlide);
        }, 5000);
        
        // Añadir event listeners a los dots
        heroDots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                goToHeroSlide(index);
                // Reiniciar intervalo
                clearInterval(heroInterval);
                heroInterval = setInterval(() => {
                    let nextSlide = (currentHeroSlide + 1) % heroSlides.length;
                    goToHeroSlide(nextSlide);
                }, 5000);
            });
        });
        
        // Pausar slider al hacer hover
        heroSlider.addEventListener('mouseenter', () => {
            clearInterval(heroInterval);
        });
        
        heroSlider.addEventListener('mouseleave', () => {
            heroInterval = setInterval(() => {
                let nextSlide = (currentHeroSlide + 1) % heroSlides.length;
                goToHeroSlide(nextSlide);
            }, 5000);
        });
    }
    
    // FUNCIÓN PARA COMBINAR SLIDERS EN MÓVIL (3 EN 1)
    function setupResponsiveSliders() {
        const promoSlidersContainer = document.querySelector('.promo-sliders-container');
        const mainPromoSlider = document.getElementById('main-promo-slider');
        const secondarySliders = document.querySelectorAll('.secondary-promo-slider');
        
        if (!promoSlidersContainer || !mainPromoSlider || secondarySliders.length === 0) return;
        
        // Crear slider combinado para móvil
        let combinedSlider = document.getElementById('combined-promo-slider');
        
        if (window.innerWidth <= 768) {
            // Modo móvil - Combinar sliders
            if (!combinedSlider) {
                combinedSlider = document.createElement('div');
                combinedSlider.id = 'combined-promo-slider';
                combinedSlider.className = 'combined-promo-slider';
                
                // Obtener todos los slides de los 3 sliders
                const mainSlides = mainPromoSlider.querySelectorAll('.promo-slide');
                const secondary1Slides = secondarySliders[0]?.querySelectorAll('.promo-slide') || [];
                const secondary2Slides = secondarySliders[1]?.querySelectorAll('.promo-slide') || [];
                
                // Combinar todos los slides
                const allSlides = [...mainSlides, ...secondary1Slides, ...secondary2Slides];
                
                allSlides.forEach((slide, index) => {
                    const clonedSlide = slide.cloneNode(true);
                    clonedSlide.classList.remove('active');
                    if (index === 0) clonedSlide.classList.add('active');
                    combinedSlider.appendChild(clonedSlide);
                });
                
                // Agregar navegación
                const navContainer = document.createElement('div');
                navContainer.className = 'combined-slider-nav';
                
                allSlides.forEach((_, index) => {
                    const dot = document.createElement('div');
                    dot.className = 'combined-slider-dot';
                    if (index === 0) dot.classList.add('active');
                    dot.addEventListener('click', () => goToCombinedSlide(index));
                    navContainer.appendChild(dot);
                });
                
                // Agregar flechas de navegación
                const prevBtn = document.createElement('div');
                prevBtn.className = 'combined-slider-nav-btn prev';
                prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
                prevBtn.addEventListener('click', () => navigateCombinedSlider(-1));
                
                const nextBtn = document.createElement('div');
                nextBtn.className = 'combined-slider-nav-btn next';
                nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
                nextBtn.addEventListener('click', () => navigateCombinedSlider(1));
                
                combinedSlider.appendChild(prevBtn);
                combinedSlider.appendChild(nextBtn);
                combinedSlider.appendChild(navContainer);
                
                // Ocultar sliders originales y mostrar el combinado
                mainPromoSlider.style.display = 'none';
                secondarySliders.forEach(slider => slider.style.display = 'none');
                promoSlidersContainer.appendChild(combinedSlider);
                
                // Iniciar slider combinado
                startCombinedSlider();
            }
        } else {
            // Modo desktop - Mostrar sliders originales
            if (combinedSlider) {
                combinedSlider.remove();
            }
            mainPromoSlider.style.display = 'block';
            secondarySliders.forEach(slider => slider.style.display = 'block');
            
            // Reiniciar sliders originales
            setupOriginalPromoSliders();
        }
    }
    
    // Variables para el slider combinado
    let currentCombinedSlide = 0;
    let combinedInterval;
    
    function startCombinedSlider() {
        const slides = document.querySelectorAll('#combined-promo-slider .promo-slide');
        if (slides.length === 0) return;
        
        // Cambiar slide cada 4 segundos (más rápido porque hay más contenido)
        combinedInterval = setInterval(() => {
            navigateCombinedSlider(1);
        }, 4000);
        
        // Pausar al hacer hover
        const combinedSlider = document.getElementById('combined-promo-slider');
        if (combinedSlider) {
            combinedSlider.addEventListener('mouseenter', () => {
                clearInterval(combinedInterval);
            });
            
            combinedSlider.addEventListener('mouseleave', () => {
                combinedInterval = setInterval(() => {
                    navigateCombinedSlider(1);
                }, 4000);
            });
        }
    }
    
    function navigateCombinedSlider(direction) {
        const slides = document.querySelectorAll('#combined-promo-slider .promo-slide');
        if (slides.length === 0) return;
        
        const dots = document.querySelectorAll('#combined-promo-slider .combined-slider-dot');
        
        slides[currentCombinedSlide].classList.remove('active');
        dots[currentCombinedSlide].classList.remove('active');
        
        currentCombinedSlide = (currentCombinedSlide + direction + slides.length) % slides.length;
        
        slides[currentCombinedSlide].classList.add('active');
        dots[currentCombinedSlide].classList.add('active');
        
        // Reiniciar intervalo
        clearInterval(combinedInterval);
        combinedInterval = setInterval(() => {
            navigateCombinedSlider(1);
        }, 4000);
    }
    
    function goToCombinedSlide(index) {
        const slides = document.querySelectorAll('#combined-promo-slider .promo-slide');
        const dots = document.querySelectorAll('#combined-promo-slider .combined-slider-dot');
        
        if (slides.length === 0) return;
        
        slides[currentCombinedSlide].classList.remove('active');
        dots[currentCombinedSlide].classList.remove('active');
        
        currentCombinedSlide = index;
        
        slides[currentCombinedSlide].classList.add('active');
        dots[currentCombinedSlide].classList.add('active');
        
        // Reiniciar intervalo
        clearInterval(combinedInterval);
        combinedInterval = setInterval(() => {
            navigateCombinedSlider(1);
        }, 4000);
    }
    
    // Configuración original de los sliders de promociones (para desktop)
    function setupOriginalPromoSliders() {
        const promoSliders = document.querySelectorAll('.main-promo-slider, .secondary-promo-slider');
        
        promoSliders.forEach(slider => {
            const slides = slider.querySelectorAll('.promo-slide');
            if (slides.length === 0) return;
            
            let currentSlide = 0;
            let promoInterval;
            
            // Función para cambiar slide
            function goToSlide(n) {
                slides[currentSlide].classList.remove('active');
                currentSlide = (n + slides.length) % slides.length;
                slides[currentSlide].classList.add('active');
            }
            
            // Añadir event listeners a las flechas de navegación
            const prevBtn = slider.querySelector('.promo-slider-nav.prev');
            const nextBtn = slider.querySelector('.promo-slider-nav.next');
            
            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    goToSlide(currentSlide - 1);
                    resetPromoInterval();
                });
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    goToSlide(currentSlide + 1);
                    resetPromoInterval();
                });
            }
            
            function resetPromoInterval() {
                clearInterval(promoInterval);
                promoInterval = setInterval(() => {
                    goToSlide(currentSlide + 1);
                }, 5000);
            }
            
            // Cambio automático cada 5 segundos
            promoInterval = setInterval(() => {
                goToSlide(currentSlide + 1);
            }, 5000);
            
            // Pausar al hacer hover
            slider.addEventListener('mouseenter', () => {
                clearInterval(promoInterval);
            });
            
            slider.addEventListener('mouseleave', () => {
                promoInterval = setInterval(() => {
                    goToSlide(currentSlide + 1);
                }, 5000);
            });
            
            // Inicializar primer slide
            goToSlide(0);
        });
    }
    
    // Configurar sliders según el tamaño de pantalla
    setupResponsiveSliders();
    setupOriginalPromoSliders();
    
    // Reconfigurar cuando cambie el tamaño de la ventana
    window.addEventListener('resize', setupResponsiveSliders);
    
    // Expandir barra de búsqueda al hacer clic
    const searchBar = document.querySelector('.search-bar');
    const searchIcon = document.querySelector('.search-icon');
    
    if (searchBar && searchIcon) {
        searchIcon.addEventListener('click', function() {
            searchBar.focus();
        });
        
        // Cerrar menú móvil si está abierto al hacer focus en búsqueda
        searchBar.addEventListener('focus', function() {
            if (navMenu && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
            }
        });
    }
});

// Sliders de Productos y Vehículos
document.addEventListener('DOMContentLoaded', function() {
    // Configuración para ambos sliders
    const sliders = [
        {
            track: document.getElementById('track-productos'),
            prev: document.getElementById('prev-productos'),
            next: document.getElementById('next-productos'),
            container: document.querySelector('.slider-container-custom')
        },
        {
            track: document.getElementById('track-vehiculos'),
            prev: document.getElementById('prev-vehiculos'),
            next: document.getElementById('next-vehiculos'),
            container: document.querySelectorAll('.slider-container-custom')[1]
        }
    ];
    
    // Ancho de un producto + gap
    const itemWidth = 265 + 25;
    
    sliders.forEach((slider) => {
        if (!slider.track || !slider.prev || !slider.next || !slider.container) {
            return;
        }
        
        // Mostrar flechas al pasar el mouse sobre el contenedor
        slider.container.addEventListener('mouseenter', function() {
            slider.prev.style.opacity = '1';
            slider.prev.style.visibility = 'visible';
            slider.next.style.opacity = '1';
            slider.next.style.visibility = 'visible';
        });
        
        // Ocultar flechas al salir del contenedor
        slider.container.addEventListener('mouseleave', function(e) {
            if (!e.relatedTarget || 
                (!e.relatedTarget.closest('.slider-control-custom') && 
                 !e.relatedTarget.closest('.slider-track-custom'))) {
                slider.prev.style.opacity = '0';
                slider.prev.style.visibility = 'hidden';
                slider.next.style.opacity = '0';
                slider.next.style.visibility = 'hidden';
            }
        });
        
        // Navegación con flechas
        slider.next.addEventListener('click', function() {
            slider.track.scrollBy({ left: itemWidth * 2, behavior: 'smooth' });
        });
        
        slider.prev.addEventListener('click', function() {
            slider.track.scrollBy({ left: -itemWidth * 2, behavior: 'smooth' });
        });
    });
});

// =============================================
// FUNCIONES GLOBALES DEL PANEL DE USUARIO
// =============================================

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