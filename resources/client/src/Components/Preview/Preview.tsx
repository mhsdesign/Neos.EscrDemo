import * as React from "react";
import styles from "./Preview.module.css";

export const Preview = (props: { text: string }) => {
    return <pre className={styles.preview}>
        <code>
            {props.text}
        </code>
    </pre>
}
