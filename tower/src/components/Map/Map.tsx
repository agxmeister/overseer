import styles from './Map.module.sass'
import Card from "@/components/Card/Card";
import {format} from "@/utils/date";
import {getColumnsTemplate} from "@/utils/grid";

type MapProps = {
    cards: Array<Card>,
}

export default function Map({cards}: MapProps) {
    const currentDate = new Date("2023-07-10");
    const endDate = new Date("2023-07-20");
    const dates = [];
    while (currentDate < endDate) {
        dates.push(format(currentDate));
        currentDate.setDate(currentDate.getDate() + 1);
    }
    return (
        <div className={styles.map} style={{gridTemplateColumns: getColumnsTemplate(dates)}}>
            {cards.length > 0 ? cards : "Loading"}
        </div>
    );
}
