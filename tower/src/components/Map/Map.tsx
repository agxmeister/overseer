import styles from './Map.module.sass'
import Card from "@/components/Card/Card";
import {format} from "@/utils/date";
import {getLinesTemplate} from "@/utils/grid";

type MapProps = {
    cards: Array<Card>,
}

export default function Map({cards}: MapProps) {
    const ids = cards.map(card => card.props.id);
    const dates = getDates(new Date("2023-07-10"), new Date("2023-07-20"));
    return (
        <div className={styles.map} style={{
            gridTemplateRows: getLinesTemplate(ids, "7em"),
            gridTemplateColumns: getLinesTemplate(dates, "7em"),
        }}>
            {cards.length > 0 ? cards : "Loading"}
        </div>
    );
}

function getDates(currentDate: Date, endDate: Date): Array<string>
{
    const dates = [];
    while (currentDate < endDate) {
        dates.push(format(currentDate));
        currentDate.setDate(currentDate.getDate() + 1);
    }
    return dates;
}
