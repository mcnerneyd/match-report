import { createRoot } from 'react-dom/client';
import App from './App';
import './Players.scss';
import './App.scss';
const container = document.getElementById('root');
const root = createRoot(container);
root.render(<App tab="home" />);
