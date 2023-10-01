'use client'

import useSWR from 'swr'

import {default as TaskMap} from "@/components/Map/Map";
import Card from "@/components/Card/Card";
import React, {useState} from "react";
import Slot from "@/components/Slot/Slot";
import {getDates, shiftDate} from "@/utils/date";
import Marker, {MarkerPosition} from "@/components/Marker/Marker";
import Link from "@/components/Link/Link";
import Task from "@/components/Task/Task";
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
import {Edit} from "@sinclair/typebox/value";

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

    const [sizeTaskId, setSizeTaskId] = useState<string|null>(null);
    const onSize = (taskId: string) => {
        setSizeTaskId(taskId);
    }
    
    const [edits, setEdits] = useState<Edits>({schedule: []});
    const setSchedule = (schedule: Schedule[]) => setEdits({...edits, schedule: schedule});

    const {data: issues, mutate: mutateIssues} = useSWR(ApiUrl.TASKS, (api: string) => fetch(api).then(res => res.json()));
    const {data: plan} = useSWR(ApiUrl.SCHEDULE, (api: string) => fetch(api).then(res => res.json()));

    const plannedIssues = issues && plan ? issues.map((issue: Issue) => {
        const result = {...issue};
        const correction = plan.find((item: Schedule) => item.key === issue.key);
        if (!correction) {
            return result;
        }
        return {
            ...issue,
            ...correction,
            links: {
                inward: mergeArrays(issue.links.inward, correction.links?.inward ?? [], (a, b) => a.key === b.key),
                outward: mergeArrays(issue.links.outward, correction.links?.outward ?? [], (a, b) => a.key === b.key),
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
        const correction = edits.schedule.find(item => item.key === mutation.taskId);
        setSchedule([...edits.schedule.filter(item => item.key !== mutation.taskId), {
            ...(correction ? cleanObject(correction): {}),
            key: mutation.taskId,
            ...cleanObject({
                estimatedBeginDate: mutation.begin,
                estimatedEndDate: mutation.end,
            })
        }]);
    }

    const onTaskResize = async (mutation: {taskId: string, begin?: string, end?: string}) => {
        const optimisticData = issues.map((issue: Issue) =>
            issue.key === mutation.taskId ? {
                ...issue,
                estimatedBeginDate: mutation.begin ?? issue.estimatedBeginDate,
                estimatedEndDate: mutation.end ?? issue.estimatedEndDate,
            } : {
                ...issue,
            });
        await mutateIssues(() => setTaskDates(mutation.taskId, mutation.begin, mutation.end), {
            optimisticData: optimisticData,
            populateCache: false,
        });
    }

    const onLink = async (outwardTaskId: string, inwardTaskId: string, type: string = LinkType.Depends) => {
        await mutateIssues(() => addLink(outwardTaskId, inwardTaskId, type),{
            populateCache: false,
        });
    }

    const onUnlink = async (linkId: number) => {
        await mutateIssues(() => removeLink(linkId),{
            populateCache: false,
        });
    }

    const scheduledIssues = plannedIssues ? plannedIssues.map((issue: Issue) => {
        const correction = edits.schedule.find(current => current.key === issue.key);
        return {
            ...issue,
            ...(correction ? cleanObject(correction): {}),
            links: {
                inward: issue.links.inward.concat(correction ? correction.links?.inward ?? [] : []),
                outward: issue.links.outward.concat(correction ? correction.links?.outward ?? [] : []),
            },
            corrected: !!correction,
        }
    }) : [];

    const links = Array.from<[string, LinkDescription]>(scheduledIssues ? scheduledIssues.reduce((acc: Map<string, LinkDescription>, issue: Issue) => {
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

    const tasks = scheduledIssues.map((issue: Issue) =>
        <Task
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
            begin={issue.estimatedBeginDate}
            end={issue.estimatedEndDate}
            card={
                <Card
                    key={issue.key}
                    id={issue.key}
                    title={issue.summary}
                    corrected={issue.corrected}
                />
            }
            onLink={onLink}
        />
    );

    const slots = sizeTaskId !== null ? dates
        .map(date =>
            <Slot
                key={date}
                id={sizeTaskId}
                position={date}
                onTask={onTask}
            />
        ) : [];

    return (
        <>
            <div tabIndex={0} onKeyDown={handleScaleKeyDown}>
                <TaskMap
                    scale={scale}
                    dates={dates}
                    tasks={tasks}
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
