<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso Denegado - Sistema Académico</title>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gradient-danger">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block" style="background: linear-gradient(45deg, #e74a3b, #c0392b); display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-ban text-white" style="font-size: 5rem; opacity: 0.3;"></i>
                            </div>
                            <div class="col-lg-6">
                                <div class="p-5 text-center">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-2">Acceso Denegado</h1>
                                        <i class="fas fa-exclamation-triangle text-danger fa-3x mb-4"></i>
                                        <p class="mb-4 text-gray-600">
                                            No tienes permisos para acceder a esta sección del sistema.
                                        </p>
                                        <p class="text-sm text-gray-500 mb-4">
                                            Si crees que esto es un error, contacta al administrador del sistema.
                                        </p>
                                    </div>
                                    
                                    <div class="text-center">
                                        <a href="login.php" class="btn btn-primary btn-user">
                                            <i class="fas fa-arrow-left"></i> Volver al Login
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>