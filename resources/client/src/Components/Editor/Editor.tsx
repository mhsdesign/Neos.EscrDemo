import * as React from "react";
import styles from "./Editor.module.css";

export const Editor = (props: { text: string, onChange: (value: string) => void }) => {
    return <pre className={styles.editor} contentEditable onInput={(e) => props.onChange(e.target.innerText)}>
        <code >
            {props.text}
        </code>
    </pre>
}
