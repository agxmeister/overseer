import styles from './Map.module.sass'
import Card from "@/components/Card/Card";

type MapProps = {
    cards: Array<Card>,
}

export default function Map({cards}: MapProps) {
    return (
        <div className={styles.map}>
            {cards.length > 0 ? cards : "Loading"}
        </div>
    );
}
