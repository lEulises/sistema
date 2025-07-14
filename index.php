<?php include 'includes/header.php'; ?>

    <main class="site-main-content">
        <section class="hero-section">
            <div class="slideshow-container">

                <div class="mySlides fade">
                    <img src="assets/img/slideshow_1.jpg" alt="Estudiantes en el campus">
                    <div class="hero-caption">
                        <div class="hero-caption-content">
                            <h1>Educación que Inspira y Transforma</h1>
                            <p>En U.E. Colegio María Auxiliadora, formamos líderes con valores y conocimientos para el futuro.</p>
                            <a href="#" class="btn btn-secondary">Conoce Nuestra Metodología</a>
                        </div>
                    </div>
                </div>

                <div class="mySlides fade">
                    <img src="assets/img/slideshow_2.jpg" alt="Aula moderna con alumnos">
                    <div class="hero-caption">
                        <div class="hero-caption-content">
                            <h1>Excelencia Académica en un Entorno Cercano</h1>
                            <p>Un ambiente donde cada estudiante es protagonista de su propio aprendizaje.</p>
                            <a href="#" class="btn btn-secondary">Explora Nuestra Oferta</a>
                        </div>
                    </div>
                </div>

                <div class="mySlides fade">
                    <img src="assets/img/slideshow_3.jpg" alt="Actividades extracurriculares en el colegio">
                    <div class="hero-caption">
                        <div class="hero-caption-content">
                            <h1>Desarrollo Integral, Más Allá del Aula</h1>
                            <p>Fomentamos el talento y las habilidades a través de un amplio programa extracurricular.</p>
                            <a href="#" class="btn btn-secondary">Descubre la Vida Estudiantil</a>
                        </div>
                    </div>
                </div>

                <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
                <a class="next" onclick="plusSlides(1)">&#10095;</a>

            </div>
            <br>

            <div style="text-align:center; padding-bottom: 20px; background-color: var(--dark-gray); border-radius: 0 0 8px 8px;">
                <span class="dot" onclick="currentSlide(1)"></span>
                <span class="dot" onclick="currentSlide(2)"></span>
                <span class="dot" onclick="currentSlide(3)"></span>
            </div>
        </section>

        <div class="container">
            </div>
    </main>
<?php include 'includes/footer.php'; ?>
