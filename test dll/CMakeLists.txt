cmake_minimum_required(VERSION 3.0.2)


PROJECT(testDLL)


add_definitions("/DLIBRARY_EXPORTS")


ADD_LIBRARY(
	${PROJECT_NAME}
	SHARED

	dllMain.cpp

	stdafx.cpp
	stdafx.h

	test.h
	test.cpp
)


if ( MSVC )
	SET_TARGET_PROPERTIES(${PROJECT_NAME} PROPERTIES COMPILE_FLAGS "/Ob0")
endif()

