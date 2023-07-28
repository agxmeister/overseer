import styles from './Map.module.sass'
import {getLinesTemplate} from "@/utils/grid";
import {ReactElement} from "react";
import {TaskProps} from "@/components/Task/Task";
import {SlotProps} from "@/components/Slot/Slot";
import {DndProvider} from "react-dnd";
import {HTML5Backend} from "react-dnd-html5-backend";

export type MapProps = {
    dates: string[],
    tasks: ReactElement<TaskProps>[],
    slots: ReactElement<SlotProps>[],
}

export default function Map({dates, tasks, slots}: MapProps)
{
    const ids = tasks.map(task => task.props.id);
    return (
        <DndProvider backend={HTML5Backend}>
            <div className={styles.map} style={{
                gridTemplateRows: getLinesTemplate(ids, "7em"),
                gridTemplateColumns: getLinesTemplate(dates, "7em"),
            }}>
                {tasks.length > 0 ? tasks : "Loading"}
                {slots.length > 0 ? slots: null}
            </div>
        </DndProvider>
    );
}
