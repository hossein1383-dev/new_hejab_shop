import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
plugins: [
laravel({
input: [
'resources/css/variables.css',
'resources/css/base.css',
'resources/css/pages/auth.css',
'resources/js/app.js',
'resources/js/auth.js',
'resources/css/components/header.css',
'resources/css/components/product-card.css',
'resources/css/components/toast.css',
'resources/css/pages/home.css',
'resources/js/header.js',
'resources/js/cart.js',
'resources/js/wishlist.js',
'resources/js/toast.js',
'resources/css/pages/cart.css',
'resources/js/cart-page.js',
'resources/css/pages/checkout.css',
'resources/js/checkout.js',
'resources/css/pages/products.css',
'resources/js/filters.js',
'resources/css/pages/admin.css',
'resources/js/admin.js',
'resources/css/pages/product.css',
'resources/js/gallery.js',
'resources/js/product.js',
'resources/js/reviews.js',
'resources/css/pages/account.css',
'resources/js/account.js',
'resources/css/pages/wallet.css',
'resources/css/components/reveal.css',
'resources/js/reveal.js',
'resources/css/components/media-loading.css',
'resources/js/media-loading.js',
'resources/css/components/footer.css',
'resources/js/blog-carousel.js',
'resources/css/pages/blog.css',
],
refresh: true,
}),
tailwindcss(),
],
server: {
watch: {
ignored: ['**/storage/framework/views/**'],
},
},
});
