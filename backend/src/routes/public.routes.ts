import { Router } from 'express';
import { publicController } from '../controllers/public.controller.js';
import { ah } from '../utils/http.js';

export const publicRoutes = Router();

publicRoutes.get('/home', ah(publicController.home));
publicRoutes.get('/categories', ah(publicController.categories));
publicRoutes.get('/products', ah(publicController.products));
publicRoutes.get('/products/:slug', ah(publicController.productDetail));
publicRoutes.get('/stores', ah(publicController.stores));
publicRoutes.get('/stores/:slug', ah(publicController.storeDetail));
