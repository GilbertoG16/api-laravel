
# WEB (Proyecto Final DS7)

## Project setup
```
php artisan migrate


-- Insertar el rol 'admin'
INSERT INTO roles (name) VALUES ('admin');

-- Insertar el rol 'superadmin'
INSERT INTO roles (name) VALUES ('superadmin');

-- Insertar el rol 'cliente'
INSERT INTO roles (name) VALUES ('cliente');


-- Crear nuevo usuario superadmin
-- Insertar el usuario con contraseña en hash
INSERT INTO users (email, password) VALUES ('gilberto.giron4@hotmail.com', '$2y$10$WhllEyWwb6u036QABSe1nesCRS8Hvo/rmf6/HMPAGAloFk2He26Xa');

-- Obtener el ID del usuario recién insertado
SET @userId = LAST_INSERT_ID();

-- Asignar el rol de 'superadmin' al usuario
INSERT INTO user_roles (userId, roleId) VALUES (@userId, (SELECT id FROM roles WHERE name = 'superadmin'));

este se crea sin perfil y con la contraseña 1234567


```


### Run
```

php artisan serve
```