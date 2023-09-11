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
import {clean} from "@/utils/misc";
import {setDates as setTaskDates} from "@/api/task";
import {addLink} from "@/api/links";
import {Type as LinkType} from "@/types/Link";

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

    const [schedule, setSchedule] = useState<Schedule[]>([]);

    const {data, mutate} = useSWR(ApiUrl.TASKS, (api: string) => fetch(api).then(res => res.json()));

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
        const correction = schedule.find(item => item.key === mutation.taskId);
        setSchedule([...schedule.filter(item => item.key !== mutation.taskId), {
            ...(correction ? clean(correction): {}),
            key: mutation.taskId,
            ...clean({
                estimatedBeginDate: mutation.begin,
                estimatedEndDate: mutation.end,
            })
        }]);
    }

    const onTaskResize = async (mutation: {taskId: string, begin?: string, end?: string}) => {
        const optimisticData = data.map((issue: Issue) =>
            issue.key === mutation.taskId ? {
                ...issue,
                estimatedBeginDate: mutation.begin ?? issue.estimatedBeginDate,
                estimatedEndDate: mutation.end ?? issue.estimatedEndDate,
            } : {
                ...issue,
            });
        await mutate(() => setTaskDates(mutation.taskId, mutation.begin, mutation.end), {
            optimisticData: optimisticData,
            populateCache: false,
        });
    }

    const onLink = (outwardTaskId: string, inwardTaskId: string) => {
        mutate(() => addLink(outwardTaskId, inwardTaskId, LinkType.Follows),{
            populateCache: false,
        });
    }

    const scheduledIssues = data ? data.map((issue: Issue) => {
        const correction = schedule.find(current => current.key === issue.key);
        return {
            ...issue,
            ...(correction ? clean(correction): {}),
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
                        schedule: schedule,
                    }}
                    setters={{
                        setScale: setScale,
                        setDates: setDates,
                        setMode: setMode,
                        setSchedule: setSchedule,
                        onTaskResize: onTaskResize,
                    }}
                />
            </div>
        </>
    );
}
