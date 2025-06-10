
import {createRoot} from "react-dom/client";
import {Demo} from "./Components";
import {createElement} from "react";

createRoot(document.getElementById('client')).render(
    createElement(Demo)
)
