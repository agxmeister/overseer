import styles from './Map.module.sass'
import {format} from "@/utils/date";
import {getLinesTemplate} from "@/utils/grid";
import {ReactElement} from "react";
import {TaskProps} from "@/components/Task/Task";
import {DndProvider} from "react-dnd";
import {HTML5Backend} from "react-dnd-html5-backend";

export type MapProps = {
    tasks: ReactElement<TaskProps>[],
}

export default function Map({tasks}: MapProps)
{
    const ids = tasks.map(task => task.props.id);
    const dates = getDates(new Date("2023-07-20"), new Date("2023-07-30"));
    return (
        <DndProvider backend={HTML5Backend}>
            <div className={styles.map} style={{
                gridTemplateRows: getLinesTemplate(ids, "7em"),
                gridTemplateColumns: getLinesTemplate(dates, "7em"),
            }}>
                {tasks.length > 0 ? tasks : "Loading"}
            </div>
        </DndProvider>
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
