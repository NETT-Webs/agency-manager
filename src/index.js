import { createRoot } from 'react-dom/client';
import { App } from './admin-app/app';
import './style.css';

const container = document.getElementById( 'agency-manager-root' );

if ( container ) {
	createRoot( container ).render( <App /> );
}
