import * as React from "react";
import styles from "./Input.module.css";

export const Button = (props: { label: string, onClick: () => void }) => {
    return <button className={styles.button} onClick={(e) => props.onClick()}>
        {props.label}
    </button>
}
