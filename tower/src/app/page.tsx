'use client'

import useSWR from 'swr'

import {default as TrackMap} from "@/components/Map/Map";
import Card from "@/components/Card/Card";
import React, {useState} from "react";
import Slot from "@/components/Slot/Slot";
import {getDates, shiftDate} from "@/utils/date";
import Marker, {MarkerPosition} from "@/components/Marker/Marker";
import Link from "@/components/Link/Link";
import Track from "@/components/Track/Track";
import Console from "@/components/Console/Console";
import {ApiUrl} from "@/constants/api";
import {Issue} from "@/types/Issue";
import {LinkDescription} from "@/types/LinkDescription";
import {Mode, Schedule} from "@/types/Schedule";
import {cleanObject, mergeArrays} from "@/utils/misc";
import {setDates as setTaskDates} from "@/api/task";
import {addLink, removeLink} from "@/api/links";
import {Type as LinkType} from "@/types/Link";
import {Edits} from "@/types/Edits";

export default function Page()
{
    const [mode, setMode] = useState<string>(Mode.View);

    const [scale, setScale] = useState<number>(1);
    const handleScaleKeyDown = (event: React.KeyboardEvent<HTMLDivElement>) => {
        if (['=', '+'].includes(event.key)) {
            setScale(scale + 0.1);
        } else if (['-', '_'].includes(event.key)) {
            setScale(scale - 0.1);
        }
    };

    const now = new Date();
    const initialDates = getDates(shiftDate(now, -15), shiftDate(now, 15));
    const [dates, setDates] = useState<string[]>(initialDates);

    const [sizeTrackId, setSizeTrackId] = useState<string|null>(null);
    const onSize = (trackId: string) => {
        setSizeTrackId(trackId);
    }

    const [edits, setEdits] = useState<Edits>({schedule: {issues: []}});
    const setSchedule = (schedule: Schedule) => setEdits({...edits, schedule: schedule});

    const {data: plan}: {data: {issues: Issue[], criticalChain: string[]}} = useSWR(ApiUrl.SCHEDULE, (api: string) => fetch(api).then(res => res.json()));
    const {data: issues, mutate: mutateIssues} = useSWR(ApiUrl.TASKS, (api: string) => fetch(api).then(res => res.json()));

    const plannedIssues = plan && issues ? plan.issues.map((issue: Issue) => {
        const details = issues.find((item: Issue) => item.key === issue.key);
        if (!details) {
            return {...issue};
        }
        return {
            ...issue,
            ...details,
            links: {
                inward: mergeArrays(issue.links?.inward ?? [], details.links?.inward ?? [], (a, b) => a.key === b.key),
                outward: mergeArrays(issue.links?.outward ?? [], details.links?.outward ?? [], (a, b) => a.key === b.key),
            },
        }
    }) : [];

    const onTask = async (mutation: {taskId: string, begin?: string, end?: string}) => {
        switch (mode) {
            case Mode.Edit:
                await onTaskSchedule(mutation);
                break;
            case Mode.View:
                await onTaskResize(mutation);
                break;
        }
    }

    const onTaskSchedule = async (mutation: {taskId: string, begin?: string, end?: string}) => {
        const correction = edits.schedule.issues.find(item => item.key === mutation.taskId);
        setSchedule({issues: [...edits.schedule.issues.filter(item => item.key !== mutation.taskId), {
            ...(correction ? cleanObject(correction): {}),
            key: mutation.taskId,
            ...cleanObject({
                begin: mutation.begin,
                end: mutation.end,
            })
        }]});
    }

    const onTaskResize = async (mutation: {taskId: string, begin?: string, end?: string}) => {
        const optimisticData = issues.map((issue: Issue) =>
            issue.key === mutation.taskId ? {
                ...issue,
                begin: mutation.begin ?? issue.begin,
                end: mutation.end ?? issue.end,
            } : {
                ...issue,
            });
        await mutateIssues(() => setTaskDates(mutation.taskId, mutation.begin, mutation.end), {
            optimisticData: optimisticData,
            populateCache: false,
        });
    }

    const onLink = async (outwardTaskId: string, inwardTaskId: string, type: string = LinkType.Sequence) => {
        await mutateIssues(() => addLink(outwardTaskId, inwardTaskId, type),{
            populateCache: false,
        });
    }

    const onUnlink = async (from: string, to: string, type: string) => {
        await mutateIssues(() => removeLink(from, to, type),{
            populateCache: false,
        });
    }

    const scheduledIssues = plannedIssues ? plannedIssues.map((issue: Issue) => {
        const correction = edits.schedule.issues.find(current => current.key === issue.key);
        if (!correction) {
            return {
                ...issue,
                corrected: false,
            }
        }
        return {
            ...issue,
            ...cleanObject(correction),
            links: {
                inward: mergeArrays(issue.links?.inward ?? [], correction.links?.inward ?? [], (a, b) => a.key === b.key),
                outward: mergeArrays(issue.links?.outward ?? [], correction.links?.outward ?? [], (a, b) => a.key === b.key),
            },
            corrected: true,
        }
    }) : [];

    const links = Array.from<[string, LinkDescription]>(scheduledIssues ? scheduledIssues.reduce((acc: Map<string, LinkDescription>, issue: Issue) => {
        if (!issue.links) {
            return acc;
        }
        Object.entries(issue.links).reduce((acc, [type, links]) => {
            links.reduce((acc, link) => {
                const key = type === 'inward' ? `${issue.key}-${link.key}` : `${link.key}-${issue.key}`;
                acc.set(key, {
                    start: type === 'inward' ? issue.key : link.key,
                    finish: type === 'inward' ? link.key : issue.key,
                    type: link.type,
                });
                return acc;
            }, acc);
            return acc;
        }, acc);
        return acc;
    }, new Map<string, LinkDescription>()) : new Map<string, LinkDescription>())
        .map(([key, link]: [string, LinkDescription]) => (
            <Link
                key={key}
                startMarkerId={link.start}
                finishMarkerId={link.finish}
                type={link.type}
            />
        ));

    const isCritical = (key: string): boolean => plan.criticalChain.includes(key);

    const tracks = scheduledIssues.map((issue: Issue) =>
        <Track
            key={issue.key}
            id={issue.key}
            markerLeft={
                <Marker
                    id={issue.key}
                    position={MarkerPosition.Left}
                    onSize={onSize}
                />
            }
            markerRight={
                <Marker
                    id={issue.key}
                    position={MarkerPosition.Right}
                    onSize={onSize}
                />
            }
            begin={issue.begin ?? ''}
            end={issue.end ?? ''}
            card={
                <Card
                    key={issue.key}
                    id={issue.key}
                    title={issue.summary ?? ''}
                    critical={isCritical(issue.key)}
                    corrected={issue.corrected}
                />
            }
            onLink={onLink}
        />
    );

    const slots = sizeTrackId !== null ? dates
        .map(date =>
            <Slot
                key={date}
                id={sizeTrackId}
                position={date}
                onTask={onTask}
            />
        ) : [];

    return (
        <>
            <div tabIndex={0} onKeyDown={handleScaleKeyDown}>
                <TrackMap
                    scale={scale}
                    dates={dates}
                    tracks={tracks}
                    slots={slots}
                    links={links}
                />
            </div>
            <div>
                <Console
                    context={{
                        issues: scheduledIssues,
                        schedule: edits.schedule,
                    }}
                    setters={{
                        setScale: setScale,
                        setDates: setDates,
                        setMode: setMode,
                        setSchedule: setSchedule,
                        onTaskResize: onTaskResize,
                        onLink: onLink,
                        onUnlink: onUnlink,
                    }}
                />
            </div>
        </>
    );
}
