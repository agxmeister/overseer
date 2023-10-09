import styles from './Map.module.sass'
import {getLinesTemplate} from "@/utils/grid";
import {createContext, ReactElement} from "react";
import {TrackProps} from "@/components/Track/Track";
import {SlotProps} from "@/components/Slot/Slot";
import {DndProvider} from "react-dnd";
import {HTML5Backend} from "react-dnd-html5-backend";

export type MapProps = {
    scale: number,
    dates: string[],
    tracks: ReactElement<TrackProps>[],
    slots: ReactElement<SlotProps>[],
    links: ReactElement<SlotProps>[],
}

export const MapContext = createContext({scale: 1, dates: [], tracks: []} as {scale: number, dates: string[], tracks: ReactElement<TrackProps>[]});

export default function Map({scale, dates, tracks, slots, links}: MapProps)
{
    const ids = tracks.map(track => track.props.id);
    const size = (7 * scale).toFixed(1);
    return (
        <DndProvider backend={HTML5Backend}>
            <div className={styles.map} style={{
                gridTemplateRows: getLinesTemplate(ids, `${size}em`),
                gridTemplateColumns: getLinesTemplate(dates, `${size}em`),
            }}>
                <MapContext.Provider value={{scale: scale, dates: dates, tracks: tracks}}>
                    {tracks.length > 0 ? tracks : "Loading"}
                    {slots.length > 0 ? slots : null}
                    {links.length > 0 ? links : null}
                </MapContext.Provider>
            </div>
        </DndProvider>
    );
}
