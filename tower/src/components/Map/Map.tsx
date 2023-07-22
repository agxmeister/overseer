import styles from './Map.module.sass'
import Task from "@/components/Task/Task";
import {format} from "@/utils/date";
import {getLinesTemplate} from "@/utils/grid";

type MapProps = {
    tasks: Array<Task>,
}

export default function Map({tasks}: MapProps) {
    const ids = tasks.map(task => task.props.id);
    const dates = getDates(new Date("2023-07-20"), new Date("2023-07-30"));
    return (
        <div className={styles.map} style={{
            gridTemplateRows: getLinesTemplate(ids, "7em"),
            gridTemplateColumns: getLinesTemplate(dates, "7em"),
        }}>
            {tasks.length > 0 ? tasks : "Loading"}
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
