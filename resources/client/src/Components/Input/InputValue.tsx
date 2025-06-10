import * as React from "react";
import styles from "./Input.module.css";

export const InputValue = (props: { name: string, value: string, onChange: (value: string) => void }) => {
    return <>
        <div className={styles.inputValueName}>{props.name}:</div>
        <input className={styles.inputValue} value={props.value} onChange={(e) => props.onChange(e.target.value)}/>
    </>
}
