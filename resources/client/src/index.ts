
import {createRoot} from "react-dom/client";
import {App} from "./App";
import {createElement} from "react";
import "./App.css";

createRoot(document.getElementById('client')).render(
    createElement(App)
)
