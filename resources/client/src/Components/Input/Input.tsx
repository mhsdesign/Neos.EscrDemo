import * as React from "react";
import styles from "./Input.module.css";

export const Input = (props: { options: string[], value: string, onChange: (value: string) => void }) => {
    return <select className={styles.input} value={props.value} onChange={(e) => props.onChange(e.target.value)}>
        {props.options.map((option) =>
            <option value={option}>{option}</option>
        )}
    </select>
}
