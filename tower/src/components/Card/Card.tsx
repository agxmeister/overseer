import styles from './Card.module.sass'
import classNames from "classnames";

export type CardProps = {
    id: string,
    title: string,
    critical?: boolean,
    corrected?: boolean,
}

export default function Card({ id, title, critical, corrected }: CardProps)
{
    return (
        <div role={"heading"} className={
            classNames(
                styles.card,
                {[styles.card_critical]: critical},
                {[styles.card_corrected]: corrected},
            )
        }>
            <div className={styles.title}>{title}</div>
        </div>
    )
}
