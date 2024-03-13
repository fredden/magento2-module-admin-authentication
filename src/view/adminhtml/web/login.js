define([], function () {
    'use strict';

    const freddenAuth = 'https://auth.fredden.com';

    return function (config, element) {
        element.innerText = '';
        const status = element.appendChild(document.createElement('p'));

        const link = element.appendChild(document.createElement('a'));

        link.target = '_blank';
        link.href = freddenAuth;
        link.innerText = 'Fredden Authentication Service';
        link.addEventListener('click', function (e) {
            e.preventDefault();
            window.open(link.href, null, 'width=750,height=400');
        });

        window.addEventListener('message', function (event) {
            if (event.origin !== freddenAuth) {
                return;
            }

            status.innerText = 'Processing...';

            const token = event.data.token;
            const req = new XMLHttpRequest();

            req.onreadystatechange = function () {
                try {
                    if (req.readyState !== 4) { return; }
                    const response = JSON.parse(req.responseText);

                    status.innerText = response.message;

                    if (response.username) {
                        // Sometimes the current password is required. Store this where a
                        // developer can access should it be required, eg on admin forms
                        localStorage.setItem('Fredden_AdminAuth.password', response.password);
                    }

                    if ([200, 201].includes(req.status) && response.status === 'success') {
                        const loc = req.getResponseHeader('location');

                        if (loc) {
                            window.location.href = loc;
                        } else {
                            window.location.reload();
                        }
                    } else if (response.username) {
                        // Fill the login form, in case user needs to manually submit
                        document.getElementById('username').value = response.username;
                        document.getElementById('login').value = response.password;
                    }
                } catch (error) {
                    status.innerText = 'Unexpected error occurred.\n\n' + error.toLocaleString();

                    const details = status.appendChild(document.createElement('details'));

                    details.innerText = req.responseText;
                }
            };

            req.open('POST', config.endpoint);
            req.setRequestHeader('content-type', 'application/x-www-form-urlencoded');
            req.send('token=' + token);
        });

        if (document.querySelector('.login-content .messages .success')) {
            if (localStorage.getItem('Fredden_AdminAuth.password')) {
                localStorage.removeItem('Fredden_AdminAuth.password');
            }
            status.innerText = '';
        } else {
            const frame = status.appendChild(document.createElement('iframe'));

            frame.src = freddenAuth + '/token?origin=' + encodeURIComponent(window.location.origin);
        }
    };
});
