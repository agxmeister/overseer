'use client'

import styles from './Card.module.sass'

type CardProps = {
    title: string,
    row: number,
    column: number,
}

export default function Card({ title, row, column }: CardProps) {
    return (
        <div
            role={"heading"}
            className={styles.container}
            style={{
                gridRow: `${row}/${row}`,
                gridColumn: `${column}/${column}`,
            }}
        >
            {title}
        </div>
    )
}
