import styles from './Card.module.sass'
import classNames from "classnames";

export type CardProps = {
    id: string,
    title: string,
    corrected?: boolean,
}

export default function Card({ id, title, corrected }: CardProps)
{
    return (
        <div role={"heading"} className={
            classNames(
                styles.card,
                {[styles.card_corrected]: corrected}
            )
        }>
            <div className={styles.title}>{title}</div>
        </div>
    )
}
