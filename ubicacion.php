<?php include 'includes/header.php'; ?>

    <main class="site-main-content">
        <section class="location-section container">
            <h1>Dónde Encontrarnos</h1>
            <p>Visita nuestras instalaciones o <a href="contacto.php">contáctanos</a> para más información.</p>

            <div class="map-container">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3926.7951622441115!2d-64.69645317560921!3d10.197283419610283!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8c2d74a0136d883b%3A0x7341998e44f2aa63!2sU.E%20Colegio%20Mar%C3%ADa%20Auxiliadora%2C%20Carrera%207%2C%20Lecher%C3%ADa%206016%2C%20Anzo%C3%A1tegui!5e0!3m2!1ses!2sve!4v1750565911313!5m2!1ses!2sve" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>



        </section>
    </main>

<?php include 'includes/footer.php'; ?>

    <script src="js/main.js"></script>
    <script>
        // JS para el menú hamburguesa
        document.addEventListener('DOMContentLoaded', () => {
            const menuToggle = document.querySelector('.menu-toggle');
            const mainNav = document.querySelector('.main-nav');
            const headerAuth = document.querySelector('.header-auth');

            menuToggle.addEventListener('click', () => {
                menuToggle.classList.toggle('active');
                mainNav.classList.toggle('active');
                headerAuth.classList.toggle('active');

                document.querySelectorAll('.nav-list .has-submenu > a').forEach(item => {
                    item.addEventListener('click', function(e) {
                        if (window.innerWidth <= 992) {
                            e.preventDefault();
                            const parentLi = this.parentElement;
                            document.querySelectorAll('.nav-list .has-submenu.active').forEach(openSub => {
                                if (openSub !== parentLi) {
                                    openSub.classList.remove('active');
                                    const openSubmenu = openSub.querySelector('.submenu');
                                    if (openSubmenu) {
                                        openSubmenu.style.display = 'none';
                                    }
                                }
                            });
                            parentLi.classList.toggle('active');
                            const submenu = this.nextElementSibling;
                            if (submenu) {
                                submenu.style.display = parentLi.classList.contains('active') ? 'block' : 'none';
                            }
                        }
                    });
                });
            });

            window.addEventListener('resize', () => {
                if (window.innerWidth > 992) {
                    menuToggle.classList.remove('active');
                    mainNav.classList.remove('active');
                    headerAuth.classList.remove('active');
                    document.querySelectorAll('.submenu').forEach(submenu => {
                        submenu.style.display = '';
                    });
                    document.querySelectorAll('.nav-list .has-submenu').forEach(li => {
                        li.classList.remove('active');
                    });
                }
            });
        });

        // NOTA: El JS del slideshow no es necesario en esta página y debería eliminarse o manejarse en un script específico para el index.
        // Si lo mantienes aquí, asegúrate de que no cause errores si los elementos .mySlides no existen.
        // Lo dejaré comentado para que decidas si moverlo o eliminarlo de esta página.
        /*
        let slideIndex = 1;
        showSlides(slideIndex);

        function plusSlides(n) {
            showSlides(slideIndex += n);
        }

        function currentSlide(n) {
            showSlides(slideIndex = n);
        }

        function showSlides(n) {
            let i;
            let slides = document.getElementsByClassName("mySlides");
            let dots = document.getElementsByClassName("dot");
            if (n > slides.length) {slideIndex = 1}
            if (n < 1) {slideIndex = slides.length}
            for (i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";
            }
            for (i = 0; i < dots.length; i++) {
                dots[i].className = dots[i].className.replace(" active", "");
            }
            slides[slideIndex-1].style.display = "block";
            dots[slideIndex-1].className += " active";
        }
        */
    </script>
</body>
</html>