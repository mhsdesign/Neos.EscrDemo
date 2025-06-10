import * as React from "react";
import styles from "./Input.module.css";

export const InputValueList = (props: { children: React.ReactElement[] }) => {
    return <div className={styles.inputValueList}>
        {props.children}
    </div>
}
