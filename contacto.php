<?php include 'includes/header.php'; ?>

<main class="site-main-content">
    <section class="contact-section container">
        <h1>Contáctanos</h1>
        <p>Estamos aquí para ayudarte. Rellena el formulario o utiliza nuestra información de contacto.</p>

            <div class="contact-info-map-section">
                <div class="contact-card">
    <h3>Información General de Contacto</h3>
    <p><i class="fas fa-map-marker-alt"></i> Carrera 7, Casco Central, Lechería Edo. Anzoátegui</p>
    <p><i class="fas fa-phone"></i> +58  281 281 5590</p>
    <p><i class="fas fa-phone"></i> +58  281 281 1805</p>
    <p><i class="fas fa-phone"></i> +58  281 281 5757</p>
    <p><i class="fas fa-clock"></i> Lunes - Viernes: 8:00 AM - 1:10 PM</p>

    <div class="email-departments-list">
        <h3>Correos por Departamento</h3>
        <ul>
            <li>
                <i class="fas fa-envelope"></i> 
                <div class="email-info">
                    <span class="department-name">Recepción:</span>
                    <a href="mailto:recepcion@colegiomariauxiliadora.com">recepcion@colegiomariauxiliadora.com</a>
                </div>
            </li>
            <li>
                <i class="fas fa-envelope"></i> 
                <div class="email-info">
                    <span class="department-name">Dirección:</span>
                    <a href="mailto:direccion@colegiomariauxiliadora.com">direccion@colegiomariauxiliadora.com</a>
                </div>
            </li>
            <li>
                <i class="fas fa-envelope"></i> 
                <div class="email-info">
                    <span class="department-name">Dpto. Administración:</span>
                    <a href="mailto:administracion@colegiomariauxiliadora.com">administracion@colegiomariauxiliadora.com</a>
                </div>
            </li>
            <li>
                <i class="fas fa-envelope"></i> 
                <div class="email-info">
                    <span class="department-name">Dpto. Evaluación:</span>
                    <a href="mailto:evaluacion@colegiomariauxiliadora.com">evaluacion@colegiomariauxiliadora.com</a>
                </div>
            </li>
            <li>
                <i class="fas fa-envelope"></i> 
                <div class="email-info">
                    <span class="department-name">Coordinación (Primaria):</span>
                    <a href="mailto:coordinacion.primaria@colegiomariauxiliadora.com">coordinacion.primaria@colegiomariauxiliadora.com</a>
                </div>
            </li>
            <li>
                <i class="fas fa-envelope"></i> 
                <div class="email-info">
                    <span class="department-name">Coordinación (E.M.G.):</span>
                    <a href="mailto:coordinacion.bachillerato@colegiomariauxiliadora.com">coordinacion.bachillerato@colegiomariauxiliadora.com</a>
                </div>
            </li>
            <li>
                <i class="fas fa-envelope"></i> 
                <div class="email-info">
                    <span class="department-name">Coordinación ENEXAI:</span>
                    <a href="mailto:enexai@colegiomariauxiliadora.com">enexai@colegiomariauxiliadora.com</a>
                </div>
            </li>
            <li>
                <i class="fas fa-envelope"></i> 
                <div class="email-info">
                    <span class="department-name">Dpto. Secretaría:</span>
                    <a href="mailto:secretaria@colegiomariauxiliadora.com">secretaria@colegiomariauxiliadora.com</a>
                </div>
            </li>
            <li>
                <i class="fas fa-envelope"></i> 
                <div class="email-info">
                    <span class="department-name">Soporte Técnico:</span>
                    <a href="mailto:soporte@colegiomariauxiliadora.com">soporte@colegiomariauxiliadora.com</a>
                </div>
            </li>
            <li>
                <i class="fas fa-envelope"></i> 
                <div class="email-info">
                    <span class="department-name">Dpto. de Orientación:</span>
                    <a href="mailto:ingrid.chacon@colegiomariauxiliadora.com">ingrid.chacon@colegiomariauxiliadora.com</a>
                </div>
            </li>
            <li>
                <i class="fas fa-envelope"></i> 
                <div class="email-info">
                    <span class="department-name">Dpto. de Orientación:</span>
                    <a href="mailto:maria.v.velasquez@colegiomariauxiliadora.com">maria.v.velasquez@colegiomariauxiliadora.com</a>
                </div>
            </li>
        </ul>
    </div>
</div>

                <div class="contact-form-card">
                    <h3>Envíanos un Mensaje</h3>
                    <form action="#" method="POST" class="contact-form">
                        <div class="form-group">
                            <label for="name">Nombre:</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Correo Electrónico:</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="subject">Asunto:</label>
                            <input type="text" id="subject" name="subject">
                        </div>
                        <div class="form-group">
                            <label for="message">Mensaje:</label>
                            <textarea id="message" name="message" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-secondary">Enviar Mensaje</button>
                    </form>
                </div>
            </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>