<?php
// partials/footer.php - Rodapé alinhado ao layout do index.php.
?>

<section class="py-8 bg-gray-100 mt-12">
    <div class="max-w-screen-2xl mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 text-sm text-gray-600">
            <div class="text-center md:text-left">
                <h3 class="font-bold text-gray-800 mb-4">Classi Livre Autos</h3>
                <p>A melhor plataforma para compra e venda de veículos usados e seminovos no Brasil.</p>
            </div>
            <div class="text-center md:text-left">
                <h3 class="font-bold text-gray-800 mb-4">Links úteis</h3>
                <ul class="space-y-2">
                    <li><a href="/anuncios/criar.php" class="hover:text-amber-600">Como anunciar</a></li>
                    <li><a href="/anuncios/listar.php" class="hover:text-amber-600">Buscar veículos</a></li>
                    <li><a href="/termos-uso.php" class="hover:text-amber-600">Termos de uso</a></li>
                    <li><a href="/politica-privacidade.php" class="hover:text-amber-600">Política de privacidade</a></li>
                </ul>
            </div>
            <div class="text-center md:text-left">
                <h3 class="font-bold text-gray-800 mb-4">Atendimento</h3>
                <ul class="space-y-2">
                    <li class="flex items-center justify-center md:justify-start gap-2">
                        <div hidden><i class="fas fa-phone-alt text-amber-500"></i> (31) 3333-3333</div>
                    </li>
                    <li class="flex items-center justify-center md:justify-start gap-2">
                        <i class="fas fa-envelope text-amber-500"></i> contato@classilivreautos.com
                    </li>
                </ul>
            </div>
            <div class="text-center md:text-left">
                <h3 class="font-bold text-gray-800 mb-4">Redes Sociais</h3>
                <div class="flex justify-center md:justify-start space-x-4 text-gray-600 text-lg">
                    <a href="https://www.instagram.com/classilivreautos?igsh=bmZuN3kyMWluMWVm" class="hover:text-amber-600"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.youtube.com/@ClassiLivreAutos" class="hover:text-amber-600"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="bg-gray-800 text-white py-6 text-center text-sm">
    <div class="max-w-screen-2xl mx-auto px-4">
        <p>© <?= date('Y'); ?> Classi Livre Autos. Todos os direitos reservados.</p>
        <p class="mt-2 text-gray-400">CNPJ: 52.160.988/0001-50 -  Developed by <a href="https://www.linkedin.com/in/jefferson-aparecido-rodrigues-miranda-095a60200/" class="text-amber-600 hover:text-amber-700">Jefferson Miranda</a> - Guanhães/MG</p>
    </div>
</footer>
</body>
</html>
