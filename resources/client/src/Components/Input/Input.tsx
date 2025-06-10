import * as React from "react";
import styles from "./Input.module.css";

export const Input = (props: { options: string[] }) => {
    return <select className={styles.input}>
        {props.options.map((option) =>
            <option value={option}>{option}</option>
        )}
    </select>
}
