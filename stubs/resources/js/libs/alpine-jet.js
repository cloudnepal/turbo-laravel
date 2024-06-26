import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

window.Alpine = Alpine;

Alpine.plugin(focus);

Alpine.start();

function initAlpineTurboPermanentFix() {
    document.addEventListener('turbo:before-render', () => {
        let permanents = document.querySelectorAll('[data-turbo-permanent]');
        let undos = Array.from(permanents).map(el => {
            el._x_ignore = true;
            return () => {
                delete el._x_ignore;
            };
        });

        document.addEventListener('turbo:render', function handler() {
            while(undos.length) undos.shift()();
            document.removeEventListener('turbo:render', handler);
        });
    });
}

initAlpineTurboPermanentFix();
