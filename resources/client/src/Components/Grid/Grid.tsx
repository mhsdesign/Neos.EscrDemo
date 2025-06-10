import * as React from "react";
import styles from "./Grid.module.css";

export const Grid = (props: { first: React.ReactElement, second: React.ReactElement, third: React.ReactElement }) => {
    return <div className={styles.grid}>
        <div className={styles.first}>{props.first}</div>
        <div className={styles.second}>{props.second}</div>
        <div className={styles.third}>{props.third}</div>
    </div>
}
