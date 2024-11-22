<nav class="bg-white shadow-md p-4 mb-6">
    <div class="flex justify-between">
        <h1 class="text-2xl font-bold"><a href="index.php">Blog</a></h1>
        <ul class="flex space-x-4">
            <li><a href="blogs.php" class="hover:underline">Ver Blogs</a></li>
            
                <li><a href="create_blog.php" class="hover:underline">Crear Blog</a></li>
            <?php if ($_SESSION['role_id'] == 1): ?>
                <li><a href="users.php" class="hover:underline">Gestión de Usuarios</a></li>
                <li><a href="admin_publish.php" class="hover:underline">Publicar anuncio</a></li>
            <?php endif; ?>
            <li><a href="account.php" class="hover:underline">Mis publicaciones</a></li>
            <li><a href="edit_profile.php" class="hover:underline">Editar Perfil</a></li>
            <li><a href="logout.php" class="hover:underline">Cerrar Sesión</a></li>

        </ul>
    </div>
</nav>
