define([], function () {
    'use strict';

    return function (config, element) {
        const password = localStorage.getItem('Fredden_AdminAuth.password');

        if (!password) {
            return;
        }

        const button = document.createElement('button');

        button.type = 'button';
        button.innerText = 'Fill with current user\'s password';
        button.classList.add('fredden-admin-auth-filler');
        element.parentNode.classList.add('fredden-admin-auth-filler-parent');
        button.addEventListener('click', function () {
            element.value = password;
            button.blur();
        });
        element.parentNode.insertBefore(button, element.nextSibling);
    };
});
