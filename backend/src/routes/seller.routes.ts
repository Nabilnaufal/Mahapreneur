import { Router } from 'express';
import { sellerController } from '../controllers/seller.controller.js';
import { authenticate, requireRole, requireStore } from '../middleware/auth.js';
import { folder, upload } from '../middleware/upload.js';
import { ah } from '../utils/http.js';

export const sellerRoutes = Router();

sellerRoutes.use(authenticate, requireRole('SELLER'));

sellerRoutes.get('/dashboard', requireStore, ah(sellerController.dashboard));

// Toko — seller bisa membuat toko walau belum punya (requireStore belum dipasang).
sellerRoutes.get('/store', ah(sellerController.getStore));
sellerRoutes.post('/store', ah(sellerController.createStore));
sellerRoutes.patch('/store', requireStore, ah(sellerController.updateStore));
sellerRoutes.post('/store/:kind(logo|banner)', requireStore, folder('stores'), upload.single('image'), ah(sellerController.uploadStoreImage));

// Produk
sellerRoutes.get('/products', requireStore, ah(sellerController.listProducts));
sellerRoutes.post('/products', requireStore, ah(sellerController.createProduct));
sellerRoutes.get('/products/:id', requireStore, ah(sellerController.getProduct));
sellerRoutes.patch('/products/:id', requireStore, ah(sellerController.updateProduct));
sellerRoutes.delete('/products/:id', requireStore, ah(sellerController.deleteProduct));
sellerRoutes.post('/products/:id/images', requireStore, folder('products'), upload.array('images', 5), ah(sellerController.addProductImages));
sellerRoutes.delete('/products/:id/images/:imageId', requireStore, ah(sellerController.deleteProductImage));

// Pesanan
sellerRoutes.get('/orders', requireStore, ah(sellerController.listOrders));
sellerRoutes.get('/orders/:id', requireStore, ah(sellerController.getOrder));
sellerRoutes.patch('/orders/:id/status', requireStore, ah(sellerController.updateOrderStatus));
sellerRoutes.post('/orders/:id/cod-paid', requireStore, ah(sellerController.markCodPaid));
