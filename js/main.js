document.addEventListener("DOMContentLoaded", function() {
    // --- Lógica para resaltar el menú de navegación activo ---
    const navLinks = document.querySelectorAll('.nav-list a');
    // Obtiene el nombre del archivo de la URL actual (ej. "contacto.php")
    // Se asegura de manejar URLs con o sin trailing slash y parámetros
    const currentPathname = window.location.pathname;
    let currentFileName = currentPathname.split('/').pop();

    // Manejar el caso de la raíz del sitio (ej. "/", que a menudo carga index.php)
    if (currentFileName === '' || currentPathname === '/') {
        currentFileName = 'index.php'; // Asumimos index.php para la página de inicio
    }
    
    // Convertir el nombre del archivo a minúsculas para una comparación sin distinción de mayúsculas/minúsculas
    currentFileName = currentFileName.toLowerCase();

    navLinks.forEach(link => {
        const linkHref = link.href;
        let linkFileName = linkHref.split('/').pop();

        // Manejar el caso de enlaces a la raíz del sitio (si su href es solo el dominio o termina en '/')
        if (linkFileName === '' || linkHref.endsWith('/')) {
            linkFileName = 'index.php'; // Asumimos index.php para enlaces a la página de inicio
        }

        linkFileName = linkFileName.toLowerCase();

        // Si el nombre del archivo del enlace coincide con el de la página actual
        // Añade la clase 'active' al elemento 'li' padre del enlace actual
        // y remueve la clase 'active' de otros enlaces.
        if (currentFileName === linkFileName) {
            // Primero, removemos 'active' de cualquier otro elemento 'li'
            document.querySelectorAll('.nav-list li.active').forEach(activeLi => {
                activeLi.classList.remove('active');
            });
            // Luego, añadimos 'active' al li padre del enlace actual
            link.parentElement.classList.add('active');
        }
    });

    // --- Lógica del menú hamburguesa para pantallas pequeñas ---
    const menuToggle = document.querySelector('.menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    const headerAuth = document.querySelector('.header-auth');
    const mediaQueryMobile = window.matchMedia('(max-width: 992px)');

    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', function() {
            mainNav.classList.toggle('active');
            menuToggle.classList.toggle('active');
            if (headerAuth) {
                headerAuth.classList.toggle('active');
            }
            document.body.classList.toggle('no-scroll', mainNav.classList.contains('active'));
        });

        document.querySelectorAll('.nav-list li.has-submenu > a').forEach(link => {
            link.addEventListener('click', function(event) {
                if (mediaQueryMobile.matches) {
                    event.preventDefault();

                    let parentLi = this.parentElement;
                    let submenu = this.nextElementSibling;

                    document.querySelectorAll('.nav-list li.has-submenu.active').forEach(openLi => {
                        if (openLi !== parentLi && !openLi.contains(parentLi)) {
                            openLi.classList.remove('active');
                            let openSubmenu = openLi.querySelector('.submenu');
                            if (openSubmenu) {
                                openSubmenu.style.display = 'none';
                            }
                        }
                    });

                    parentLi.classList.toggle('active');

                    if (submenu) {
                        if (parentLi.classList.contains('active')) {
                            submenu.style.display = 'block';
                        } else {
                            submenu.style.display = 'none';
                        }
                    }
                }
            });
        });

        const resetMenu = () => {
            if (!mediaQueryMobile.matches) {
                mainNav.classList.remove('active');
                menuToggle.classList.remove('active');
                if (headerAuth) {
                    headerAuth.classList.remove('active');
                }
                document.body.classList.remove('no-scroll');

                document.querySelectorAll('.submenu').forEach(submenu => {
                    submenu.style.display = '';
                });
                document.querySelectorAll('.nav-list li.has-submenu').forEach(li => {
                    li.classList.remove('active');
                });
            }
        };

        mediaQueryMobile.addEventListener('change', resetMenu);
        resetMenu();

        document.addEventListener('click', function(event) {
            if (mediaQueryMobile.matches && !mainNav.contains(event.target) && !menuToggle.contains(event.target) && (headerAuth ? !headerAuth.contains(event.target) : true)) {
                mainNav.classList.remove('active');
                menuToggle.classList.remove('active');
                if (headerAuth) {
                    headerAuth.classList.remove('active');
                }
                document.body.classList.remove('no-scroll');

                document.querySelectorAll('.nav-list li.has-submenu.active').forEach(openLi => {
                    openLi.classList.remove('active');
                    let openSubmenu = openLi.querySelector('.submenu');
                    if (openSubmenu) {
                        openSubmenu.style.display = 'none';
                    }
                });
            }
        });
    }

    // --- Lógica del Slideshow (se mantiene igual, no necesita cambios por el menú) ---
    let slideIndex = 0;
    let slideshowTimer;

    function showSlides() {
        let i;
        let slides = document.getElementsByClassName("mySlides");
        let dots = document.getElementsByClassName("dot");

        if (slides.length === 0) return;

        for (i = 0; i < slides.length; i++) {
            slides[i].style.display = "none";
        }
        for (i = 0; i < dots.length; i++) {
            dots[i].className = dots[i].className.replace(" active", "");
        }

        slideIndex++;
        if (slideIndex > slides.length) {
            slideIndex = 1;
        }

        slides[slideIndex - 1].style.display = "block";
        dots[slideIndex - 1].className += " active";

        clearTimeout(slideshowTimer);
        slideshowTimer = setTimeout(showSlides, 5000);
    }

    window.plusSlides = function(n) {
        clearTimeout(slideshowTimer);
        showSpecificSlide(slideIndex += n);
        slideshowTimer = setTimeout(showSlides, 5000);
    }

    window.currentSlide = function(n) {
        clearTimeout(slideshowTimer);
        showSpecificSlide(slideIndex = n);
        slideshowTimer = setTimeout(showSlides, 5000);
    }

    function showSpecificSlide(n) {
        let i;
        let slides = document.getElementsByClassName("mySlides");
        let dots = document.getElementsByClassName("dot");

        if (slides.length === 0) return;

        if (n > slides.length) {
            slideIndex = 1
        }
        if (n < 1) {
            slideIndex = slides.length
        }

        for (i = 0; i < slides.length; i++) {
            slides[i].style.display = "none";
        }
        for (i = 0; i < dots.length; i++) {
            dots[i].className = dots[i].className.replace(" active", "");
        }

        slides[slideIndex - 1].style.display = "block";
        dots[slideIndex - 1].className += " active";
    }

    const slideshowContainer = document.querySelector('.slideshow-container');
    if (slideshowContainer) {
        slideshowContainer.addEventListener('mouseenter', () => {
            clearTimeout(slideshowTimer);
        });
        slideshowContainer.addEventListener('mouseleave', () => {
            slideshowTimer = setTimeout(showSlides, 5000);
        });
    }

    showSlides();
});